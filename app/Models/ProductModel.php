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
    /**
     * @var string
     */
    protected $tableName = 'products';

    /**
     * @var array
     */
    protected $fillable = ['title' => 'string', 'description' => 'string',
                           'price' => 'int', 'active' => 'int', 'category_id' => 'int'];

    /**
     * Returns filtered Ads list
     *
     * @param CategoryModel $categoryModel
     * @param $request
     * @return array|bool
     */
    public function filtered(CategoryModel $categoryModel, $request) {

        if (!$request instanceof Request) {
            return false;
        }

        $config_limit = Config::getInstance()->get('limit');
        $sort_map = ['newest'    => 'ORDER BY created_at DESC',
                     'oldest'    => 'ORDER BY created_at ASC',
                     'cheaper'   => 'ORDER BY price ASC',
                     'expensive' => 'ORDER BY price DESC'];

        $category_id = $request->get('category_id', null, 'int');
        $page = $request->get('page', 1, 'int');
        $search = $request->get('search', null, 'string');
        $active = $request->get('active', null, 'int');

        $category_sql = null;
        $search_sql = null;
        $sort_sql = $sort_map[$request->get('sort_by', null, 'string')];

        if ($category_id === 1) {
            $active_sql = $active === 1 ? 'WHERE active = 1' : 'WHERE active = 0';
        }
        else {
            $active_sql = $active === 1 ? 'AND active = 1' : 'AND active = 0';
            $category_ids = implode(',', $categoryModel->getDescendantsAndSelfIds($category_id));
            $category_sql = "WHERE category_id IN ($category_ids)";
        }

        $limit_sql = $page === 1 ? sprintf('LIMIT %s', $config_limit) :
            sprintf('LIMIT %s, %s', $config_limit * ($page - 1), $config_limit);

        if (!empty($search)) {
            $search_sql = "AND `title` LIKE '%$search%' OR `description` LIKE '%$search%'";
        }

        $sql = sprintf("SELECT COUNT(*) FROM %s %s %s %s %s", $this->tableName, $category_sql, $active_sql,
            $search_sql, $sort_sql);

        $this->dbo->setQuery($sql)->getResult($this);
        $pages = intval(ceil($this->{'COUNT(*)'} / $config_limit));

        $sql = sprintf("SELECT * FROM %s %s %s %s %s %s", $this->tableName, $category_sql, $active_sql,
            $search_sql, $sort_sql, $limit_sql);

        $products = $this->dbo->setQuery($sql)->getList(get_class($this));

        return array('products' => $products, 'pages' => $pages);
    }

    /**
     * Returns raw Ads list
     *
     * @param string $columnName
     * @return array
     */
    public function getLimitList( string $columnName = '*' ) {
        $config_limit  = Config::getInstance()->get('limit');

        $sql = sprintf("SELECT COUNT(*) FROM `%s`",
            $this->tableName);

        $this->dbo->setQuery($sql)->getResult($this);
        $pages = intval(ceil($this->{'COUNT(*)'} / $config_limit));

        $sql = sprintf("SELECT `%s` FROM `%s` LIMIT %u",
            $columnName, $this->tableName, $config_limit);

        $products = $this->dbo->setQuery($sql)->getList(get_class($this));

        return array('products' => $products, 'pages' => $pages);
    }
}