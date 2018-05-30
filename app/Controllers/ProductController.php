<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use Mindk\Framework\File\File;
use Mindk\Framework\Config\Config;
use Mindk\Framework\Http\Request\Request;
use Mindk\Framework\Helpers\Helper;
use Mindk\Framework\Exceptions\NotFoundException;
use Mindk\Framework\Auth\AuthService;
use Mindk\Framework\Http\Response\JsonResponse;


/**
 * Product controller
 *
 * Class ProductController
 * @package App\Controllers
 */
class ProductController
{
    /**
     * Products index page
     *
     * @param ProductModel $model
     * @param CategoryModel $categoryModel
     * @param Request $request
     * @return array
     */
    function index(ProductModel $model, CategoryModel $categoryModel, Request $request){
        return $request->has('state') ? $model->filtered($categoryModel, $request) : $model->getList();
    }

    /**
     * Single product page
     *
     * @param ProductModel $model
     * @param $id
     * @return object
     * @throws NotFoundException
     */
    function show(ProductModel $model, $id){

        $item = $model->load($id);

        // Check if record exists
        if(empty($item)) {
            throw new NotFoundException('Product with id ' . $id . ' not found');
        }

        return $item;
    }

    /**
     * Create new product
     *
     * @param ProductModel $product
     * @param Request $request
     * @param File $file
     * @return array
     * @throws \Mindk\Framework\Exceptions\ModelException
     */
    function create(ProductModel $product, Request $request, File $file){
        $errors = [];
        $data = [];
        $filename= null;
        $config = Config::getInstance();
        $image = $request->hasUploadFile('image') ? $request->get('image') : null;
        $data['title'] = $request->check('title') ? $request->get('title', null, 'string') : array_push($errors, ['title' => 'Field is required']);
        $data['description'] = $request->check('description') ? $request->get('description', null, 'string') : array_push($errors, ['description' => 'Field is required']);
        $data['price'] = $request->check('price') ? $request->get('price', null, 'int') : array_push($errors, ['price' => 'Field is required']);
        $data['category_id'] = $request->check('category_id') ? $request->get('category_id', null, 'int') : array_push($errors, ['category_id' => 'Field is required']);
        $data['user_id'] = AuthService::getUserId();
        if($file->isImage($image) && $file->isValidSize($image, $config->max_file_size) && $image['error'] === 0){
            $name = !empty($data['title']) ? $data['title'] : 'product';
            $filename = sprintf('%s-%s.%s', Helper::transliterate($name), rand(0,4), $file->extension($image['name']));
            $data['image'] = $filename;
        }
        else {
            $errors['image'] = 'File not a image (available extensions: jpg, png) or size over 2 Mb!!';
        }

        $result = ['SUCCESS'];
        $code = 201;
        if(empty($errors)){
            $product->create($data);
            $file->move($image['tmp_name'], Helper::getPath($config, 'uploads') . $filename);
        }
        else{
            $result = $errors;
            $code = 400;
        }

        return new JsonResponse($result, $code);
    }

    /**
     * Delete product
     *
     * @param ProductModel $model
     * @param File $file
     * @param $id
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function delete(ProductModel $model, File $file, $id){
        $product = $model->load($id);
        if(!$product){
            throw new NotFoundException('Model not found');
        }
        $user = AuthService::getUser();
        $config = Config::getInstance();
        if($product->user_id == $user->id || $user->getRole() == 'admin'){
            $file->delete(Helper::getPath($config, 'uploads') . $product->image);
            $product->delete($id);
            $response = new JsonResponse('Deleted', 200);
        }
        else {
            $response = new JsonResponse('Access denied', 403);
        }

        return $response;
    }

    public function update(ProductModel $model, File $file, Request $request, $id){
        $product = $model->load($id);
        if(!$product){
            throw new NotFoundException('Model not found');
        }
        $user = AuthService::getUser();
        $config = Config::getInstance();
        if($product->user_id == $user->id || $user->getRole() == 'admin'){
            $product->title = $request->get('title', null, 'string') ?? $product->title;
            $product->description = $request->get('description', null, 'string') ?? $product->description;
            $product->price = $request->get('price', null, 'int') ?? $product->price;
            $product->category_id = $request->get('category_id', null, 'int') ?? $product->category_id;
            $image = $request->hasUploadFile('image') ? $request->get('image') : null;

            if($file->isImage($image) && $file->isValidSize($image, $config->max_file_size) && $image['error'] === 0){
                $file->delete(Helper::getPath($config, 'uploads') . $product->image);
                $filename = sprintf('%s-%s.%s', Helper::transliterate($product->title), rand(0,4), $file->extension($image['name']));
                $product->image = $filename;
                $file->move($image['tmp_name'], Helper::getPath($config, 'uploads') . $product->image);
            }

            $product->save();
            $response = new JsonResponse('Updated', 200);
        }
        else {
            $response = new JsonResponse('Access denied', 403);
        }

        return $response;
    }
}