<?php

namespace App\Controllers;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use Mindk\Framework\Http\Request\Request;
use Mindk\Framework\Exceptions\NotFoundException;

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
     * Create product
     */
    function create(){
        return "SUCCESS";
        //@TODO: Implement this
    }
}