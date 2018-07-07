<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use Mindk\Framework\File\File;
use Mindk\Framework\Config\Config;
use Mindk\Framework\Http\Request\Request;
use Mindk\Framework\Helpers\Helper;
use Mindk\Framework\Exceptions\NotFoundException;
use Mindk\Framework\Exceptions\FileException;
use Mindk\Framework\Auth\AuthService;
use Mindk\Framework\Http\Response\JsonResponse;
use Mindk\Framework\Validation\Validation;


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
    public function index(ProductModel $model, CategoryModel $categoryModel, Request $request) {

        return $request->has('state') ? $model->filtered($categoryModel, $request) : $model->getLimitList();
    }

    /**
     * Single product page
     *
     * @param ProductModel $model
     * @param $id
     * @return array
     * @throws NotFoundException
     */
    public function show(ProductModel $model, $id) {

        $product = $model->findOrFail($id);
        $user = AuthService::getUser();
        $access = false;
        if(!empty($user)){
            if($user->id === $product->user_id || $user->getRole() == 'admin'){
                $access = true;
            }
        }

        return ['product' => $product, 'access' => $access];
    }

    /**
     * Create new product
     *
     * @param ProductModel $model
     * @param Request $request
     * @param File $file
     * @param Validation $validation
     * @return JsonResponse
     * @throws FileException
     * @throws NotFoundException
     * @throws \Mindk\Framework\Exceptions\ModelException
     * @throws \Mindk\Framework\Exceptions\ValidationException
     */
    public function create(ProductModel $model, Request $request, File $file, Validation $validation)
    {
        $rules = [
            'title' => 'required|string|min:5',
            'description' => 'required|string|min:10',
            'price' => 'required|int',
            'category_id' => 'required|int',
            'image' => 'required|file',
            'active' => 'required|int'
        ];

        $config = Config::getInstance();
        $errors = $validation->validate($request, $rules);

        if(is_array($errors)) {
            return new JsonResponse($errors, 400);
        }

        $data = [];
        $image = $request->get('image');
        $data['title'] = $request->get('title', null, 'string');
        $data['description'] = $request->get('description', null, 'string');
        $data['price'] = $request->get('price', null, 'int');
        $data['category_id'] = $request->get('category_id', null, 'int');
        $data['user_id'] = AuthService::getUserId();
        $data['active'] = $request->get('active', null, 'int');

        $result = ['SUCCESS'];
        $code = 201;
        if (empty($errors)) {
            $data['image'] = $this->saveImage($model, $file, $image, $data['title']);
            $file->move($image['tmp_name'], Helper::getPath($config->uploads) . $data['image']);
            $model->create($data);
        } else {
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
     * @param Config $config
     * @param $id
     * @return JsonResponse
     * @throws NotFoundException
     */
    public function delete(ProductModel $model, File $file, Config $config, $id)
    {
        $product = $model->load($id);
        if (!$product) {
            throw new NotFoundException('Model not found');
        }
        $user = AuthService::getUser();
        if ($product->user_id == $user->id || $user->getRole() == 'admin') {
            $file->delete(Helper::getPath($config->uploads) . $product->image);
            $product->delete($id);
            $response = new JsonResponse('Deleted', 200);
        } else {
            $response = new JsonResponse('Access denied', 403);
        }

        return $response;
    }

    /**
     * Update product
     * 
     * @param ProductModel $model
     * @param File $file
     * @param Request $request
     * @param Validation $validation
     * @param $id
     * @return JsonResponse
     * @throws FileException
     * @throws NotFoundException
     * @throws \Mindk\Framework\Exceptions\ValidationException
     */
    public function update(ProductModel $model, File $file, Request $request, Validation $validation, $id)
    {
        $product = $model->findOrFail($id);
        $config = Config::getInstance();
        $user = AuthService::getUser();
        if ($product->user_id == $user->id || $user->getRole() == 'admin') {
            $rules = [
                'title' => 'string|min:5',
                'description' => 'string|min:10',
                'price' => 'int',
                'category_id' => 'int',
                'active' => 'int',
                'image' => 'file'
            ];

            $errors = $validation->validate($request, $rules);
            if(is_array($errors)) {
                return new JsonResponse($errors, 400);
            }

            if ($request->getMethod() === 'PUT') {
                $product->fill($request);
            }
            else {
                $image = $request->get('image');
                $product->image = $this->saveImage($model, $file, $image, null, $id);
                $file->move($image['tmp_name'], Helper::getPath($config->uploads) . $product->image);
            }
            $product->save();
            $response = new JsonResponse('Updated', 200);
        } else {
            $response = new JsonResponse('Access denied', 403);
        }

        return $response;
    }

    /**
     * Save upload image
     *
     * @param ProductModel $model
     * @param File $file
     * @param $image
     * @param null $title
     * @param null $id
     * @return string
     * @throws FileException
     * @throws NotFoundException
     */
    public function saveImage(ProductModel $model, File $file, $image, $title = null, $id = null)
    {
        $product = is_null($id) ? $model : $model->findOrFail($id);
        $config = Config::getInstance();
        if ($file->isImage($image) && $file->isValidSize($image, $config->max_file_size) && $image['error'] === 0) {
            if (!empty($product->image)) {
                $file->delete(Helper::getPath($config->uploads) . $product->image);
            }
            if (is_null($id)) {
                $name = !is_null($title) ? $title : 'product';
                $filename = sprintf('%s-%s.%s', Helper::transliterate($name), rand(0, 100), $file->extension($image['name']));
            } else {
                $filename = sprintf('%s-%s.%s', Helper::transliterate($product->title), rand(0, 100), $file->extension($image['name']));
            }

            return $filename;
        } else {
            $file_error_mapping = [
                1 => 'UPLOAD_ERR_INI_SIZE',
                2 => 'UPLOAD_ERR_FORM_SIZE',
                3 => 'UPLOAD_ERR_PARTIAL',
                4 => 'UPLOAD_ERR_NO_FILE',
                6 => 'UPLOAD_ERR_NO_TMP_DIR',
                7 => 'UPLOAD_ERR_CANT_WRITE',
                8 => 'UPLOAD_ERR_EXTENSION'
            ];

            $errors = [];
            $file->isImage($image) ?: array_push($errors, 'file is not image');
            $file->isValidSize($image, $config->max_file_size) ?: array_push($errors, 'file is not valid size (max: ' . $config->max_file_size . 'KB)');
            $image['error'] === 0 ? null : array_push($errors, $file_error_mapping[$image['error']]);

            throw new FileException('Errors: ' . implode(',', $errors));
        }

    }
}