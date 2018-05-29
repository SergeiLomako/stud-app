<?php
/**
 * Created by PhpStorm.
 * User: flameseeker
 * Date: 23.05.18
 * Time: 1:16
 */

namespace App\Models;

use Mindk\Framework\Models\Model;


class CategoryModel extends Model
{
    protected $tableName = 'categories';

    /**
     * Build an array for the front
     *
     * @return array
     */
    public function getArrayList(){
        $categories = $this->getWithoutRoot();
        $ancestor = null;
        $array_categories = [];
        foreach($categories as $category){
            if($this->hasDescendants($category->id)){
                $array_categories[$category->name] = ['category' => $category, 'children' => array()];
                $ancestor = $category;
            }
            else {
                if($ancestor && $ancestor->_lft < $category->_lft && $ancestor->_rgt > $category->_rgt){
                    array_push($array_categories[$ancestor->name]['children'], $category);
                }
                else {
                    $array_categories[$category->name] = ['category' => $category];
                }
            }
        }
        
        return $array_categories;
    }

    /**
     * Check category descendants
     *
     * @param $id
     * @return bool
     */
    public function hasDescendants($id){
        $category = $this->load($id);
        if($category){
            return $category->_rgt - $category->_lft > 1;
        }
    }

    /**
     * Get all categories without root
     *
     * @return mixed
     */
    public function getWithoutRoot(){
        $sql = sprintf("SELECT * FROM `%s` WHERE `level` != 1", (string)$this->tableName);
            
        return $this->dbo->setQuery($sql)->getList(get_class($this));
    }

    /**
     * Get category descendants
     *
     * @param $id
     * @return array
     */
    public function getDescendants($id){
        $descendants = [];
        if($this->hasDescendants($id)){
            $category = $this->load($id);
            if($category){
                $sql = sprintf("SELECT * FROM `%s` WHERE `_rgt` < %s AND `_lft` > %s",
                                $this->tableName, (int) $category->_rgt, (int) $category->_lft);
                $descendants = $this->dbo->setQuery($sql)->getList(get_class($this));
            }
        }

        return $descendants;
    }

    /**
     * Build array with category and her descendants ids
     *
     * @param $id
     * @return array
     */
    public function getDescendantsAndSelfIds($id){
        $ids = [];
        $category = $this->load($id);
        if($category){
            array_push($ids, $id);
            if($this->hasDescendants($id)){
                $descendants = $this->getDescendants($id);
                foreach($descendants as $model){
                    array_push($ids, (int) $model->id);
                }
            }
        }

        return $ids;
    }
}