<?php
/**
 * Created by PhpStorm.
 * User: flameseeker
 * Date: 23.05.18
 * Time: 1:14
 */

namespace App\Controllers;

use App\Models\CategoryModel;


class CategoryController
{
    /**
     * Get category list
     * 
     * @param CategoryModel $model
     * @return array
     */
    public function index(CategoryModel $model){
        return $model->getArrayList();
    }
}