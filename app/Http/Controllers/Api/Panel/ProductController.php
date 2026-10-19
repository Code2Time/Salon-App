<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CurrentSalon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request, CurrentSalon $context)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'stock' => ['nullable', Rule::in(['available', 'empty'])],
        ]);
        $query = Product::query()->where('salon_id', $context->get()->id)->with('services')->latest();
        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('is_active', $status === 'active');
        }
        if ($stock = $filters['stock'] ?? null) {
            $query->where('stock', $stock === 'available' ? '>' : '=', 0);
        }

        return ProductResource::collection($query->paginate(6));
    }

    public function options(CurrentSalon $context)
    {
        return response()->json(['data' => $context->get()->services()->orderBy('name')->get(['id', 'name', 'is_active'])]);
    }

    public function store(Request $request, CurrentSalon $context)
    {
        return $this->save($request, new Product(['salon_id' => $context->get()->id]), $context);
    }

    public function update(Request $request, Product $product, CurrentSalon $context)
    {
        abort_unless($product->salon_id === $context->get()->id, 404);

        return $this->save($request, $product, $context);
    }

    public function destroy(Product $product, CurrentSalon $context)
    {
        abort_unless($product->salon_id === $context->get()->id, 404);
        $product->delete();

        return response()->json(['message' => 'محصول حذف شد.']);
    }

    private function save(Request $request, Product $product, CurrentSalon $context)
    {
        if ($request->has('recommendations_json')) {
            $request->validate(['recommendations_json' => ['required', 'json', 'max:250000']]);
            $request->merge(['recommendations' => json_decode($request->input('recommendations_json'), true)]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
            'recommendations' => ['present', 'array', 'max:100'],
            'recommendations.*' => ['required', 'array:service_id,recommendation_text,recommendation_enabled,discount_enabled,discount_percent'],
            'recommendations.*.service_id' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where(fn ($query) => $query->where('salon_id', $context->get()->id)->whereNull('deleted_at'))],
            'recommendations.*.recommendation_text' => ['nullable', 'string', 'max:1000'],
            'recommendations.*.recommendation_enabled' => ['required', 'boolean'],
            'recommendations.*.discount_enabled' => ['required', 'boolean'],
            'recommendations.*.discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $links = [];
        foreach ($data['recommendations'] as $index => $link) {
            if ($link['discount_enabled'] && $link['discount_percent'] < 1) {
                throw ValidationException::withMessages(["recommendations.{$index}.discount_percent" => 'درصد تخفیف باید بین ۱ تا ۱۰۰ باشد.']);
            }
            if ($link['recommendation_enabled'] && blank($link['recommendation_text'] ?? null)) {
                throw ValidationException::withMessages(["recommendations.{$index}.recommendation_text" => 'متن پیشنهاد مراقبتی را وارد کنید.']);
            }
            $id = $link['service_id'];
            unset($link['service_id']);
            $link['discount_percent'] = $link['discount_enabled'] ? $link['discount_percent'] : 0;
            $links[$id] = $link;
        }
        $oldImage = $product->image;
        $newImage = $request->file('image')?->store('products', 'public');
        abort_if($request->hasFile('image') && ! $newImage, 503, 'ذخیره تصویر ناموفق بود؛ دوباره تلاش کنید.');
        $created = ! $product->exists;
        try {
            DB::transaction(function () use ($product, $data, $newImage, $links, $request) {
                if ($product->exists) {
                    Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                }
                $product->fill(collect($data)->only(['name', 'description', 'price', 'stock', 'is_active'])->all());
                $product->name = trim($product->name);
                if ($newImage || $request->boolean('remove_image')) {
                    $product->image = $newImage;
                }
                $product->save();
                $product->services()->sync($links);
            });
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }
        if ($oldImage && $oldImage !== $product->image) {
            Storage::disk('public')->delete($oldImage);
        }

        return (new ProductResource($product->load('services')))->response()->setStatusCode($created ? 201 : 200);
    }
}
