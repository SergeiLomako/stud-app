<?php

namespace App\Models;

use Mindk\Framework\Models\Model;
use Mindk\Framework\Config\Config;
use Mindk\Framework\Http\Request\Request;

/**
 * Class ProductModel
 *
 * @package App\Models
 */
class ProductModel extends Model
{
    protected $tableName = 'products';
    protected $fillable = ['title' => 'string', 'description' => 'string',
                           'price' => 'int', 'active' => 'int', 'category_id' => 'int'];

    /**
     * Gets filtered product list
     *
     * @param CategoryModel $categoryModel
     * @param $request
     * @return array|bool
     */
    public function filtered(CategoryModel $categoryModel, $request){
        if(!$request instanceof Request){
            return false;
        }
        $config = Config::getInstance();
        $conf_limit = $config->get('limit');
        $sorting_map = ['newest' => 'ORDER BY created_at DESC',
                        'oldest' => 'ORDER BY created_at ASC',
                        'cheap' => 'ORDER BY price ASC',
                        'expensive' => 'ORDER BY price DESC'];

        $category = null;
        $searched = null;
        $category_id = $request->get('category', null, 'int');
        $page = $request->get('page', null, 'int');
        $search = $request->get('search', null, 'string');
        $sorting = $sorting_map[$request->get('sorting')];

        if($request->get('category', null, 'int') == 0){
            $active = $request->get('active', null, 'int') == 1 ? 'WHERE active = 1' : 'WHERE active = 0';
        }
        else {
            $active = $request->get('active', null, 'int') == 1 ? 'AND active = 1' : 'AND active = 0';
            $ids = implode(',', $categoryModel->getDescendantsAndSelfIds($category_id));
            $category = "WHERE category_id IN ($ids)";
        }

        $limit = $page == 1 ? sprintf('LIMIT %s', $conf_limit)
                 : sprintf('LIMIT %s, %s', $conf_limit * ($page - 1), $conf_limit);

        if(!empty($search)){
            $searched = "AND `title` LIKE '%$search%' OR `description` LIKE '%$search%'";
        }

        $sql = sprintf("SELECT COUNT(*) FROM %s %s %s %s %s", $this->tableName, $category, $active,
            $searched, $sorting);

        $this->dbo->setQuery($sql)->getResult($this);
        $pages = intval(ceil($this->{'COUNT(*)'} / $config->limit));

        $sql = sprintf("SELECT * FROM %s %s %s %s %s %s", $this->tableName, $category, $active,
            $searched, $sorting, $limit);

        $products = $this->dbo->setQuery($sql)->getList(get_class($this));

        return array('products' => $products, 'pages' => $pages);

    }

    /**
     * Gets all products list
     *
     * @param string $columnName
     * @return array
     */
    public function getLimitList( string $columnName = '*' ) {
        $config  = Config::getInstance();

        $sql = sprintf("SELECT COUNT(*) FROM `%s`",
            $this->tableName);

        $this->dbo->setQuery($sql)->getResult($this);
        $pages = intval(ceil($this->{'COUNT(*)'} / $config->limit));

        $sql = sprintf("SELECT `%s` FROM `%s` LIMIT %u",
            $columnName, $this->tableName, $config->limit);

        $products = $this->dbo->setQuery($sql)->getList(get_class($this));

        return array('products' => $products, 'pages' => $pages);
    }
}