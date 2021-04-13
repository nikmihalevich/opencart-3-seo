<?php

class ControllerExtensionModuleSEO extends Controller {

    public function __construct ($registry) {
        $parentResult = parent::__construct($registry);
        $this->load->model('extension/module/seo');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/information');

        return $parentResult;
    }

    public function install () {

        $this->model_extension_module_seo->install();
    }

    public function uninstall () {
        $this->model_extension_module_seo->uninstall();
    }

    private function getTokenParam () {
        if (isset($this->session->data['user_token'])) {
            return 'user_token=' . $this->session->data['user_token'];
        }
        else {
            return 'token=' . $this->session->data['token'];
        }
    }

    private function createUrl ($url) {
        return $this->url->link($url, $this->getTokenParam(), 'SSL');
    }

    public function index () {
        $data = array();
//        $this->install();

        $validKeys = $this->model_extension_module_seo->getSettings();

        $this->load->model('localisation/language');
        $this->language->load('extension/module/seo');

        $data['heading_title'] = $this->language->get('heading_title');
        $data['breadcrumbs'] = $this->breadcrumbs();
        $data['action'] = $this->createUrl('extension/module/seo');

        $data = $this->model_extension_module_seo->getSettings();

        $request_done = false;

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $post = $this->request->post;
            if (isset($post)) {
                $data = $post;

                foreach ($validKeys as $k => $v) {
                    if ($validKeys[$k] != $post[$k]) {
                        $this->setMetaInfo($k, $post);
                    }
                    if($k == "seo_articles_refresh_keyword" || $k == "seo_articles_refresh_descr") {
                        $post[$k] = "0";
                    }
                }
            }

            $this->model_extension_module_seo->saveSettings($post);
            $request_done = true;
//            $this->response->redirect($this->createUrl('extension/module/seo/index'));
        }

        $data['request_done'] = $request_done;
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/seo', $data));

    }

    private function setMetaInfo($key, $data) {
        $data[$key] = trim($data[$key]);

        switch ($key) {
            case "seo_categories_keywords":
                $total_categories = $this->model_catalog_category->getCategories();
                foreach ($total_categories as $k => $v) {
                    $keywords = $data[$key];

                    $cat_name = $total_categories[$k]['name'];
                    $cat_name = explode("&nbsp;&nbsp;&gt;&nbsp;&nbsp;", $cat_name);
                    $cat_name = implode(' ', $cat_name);
                    $cat_name = mb_strtolower($cat_name);

                    $keywords = $this->concatKeywords($keywords, $cat_name, NULL );

                    $category_meta_info  = $this->model_catalog_category->getCategoryDescriptions($total_categories[$k]['category_id']);
                    foreach ($category_meta_info as $kk => $vv) {
                        if(empty($category_meta_info[$kk]['meta_keyword'])) {
                            $this->model_extension_module_seo->editCategoryKeywords($keywords, $total_categories[$k]['category_id']);
                        } else {
                            if($data['seo_categories_apply'] == 1) {
                                $this->model_extension_module_seo->editCategoryKeywords($keywords, $total_categories[$k]['category_id']);
                            } else {
                                break;
                            }
                        }
                    }
                }
                break;


            case "seo_categories_description":
                $total_categories = $this->model_catalog_category->getCategories();
                foreach ($total_categories as $k => $v) {
                    $description = $data[$key];

                    $cat_name = $total_categories[$k]['name'];
                    $cat_name = explode("&nbsp;&nbsp;&gt;&nbsp;&nbsp;", $cat_name);
                    $cat_name = implode(' ', $cat_name);
                    $cat_name = mb_strtolower($cat_name);

                    $description = $description . ' - ' . $cat_name;

                    $category_meta_info  = $this->model_catalog_category->getCategoryDescriptions($total_categories[$k]['category_id']);
                    foreach ($category_meta_info as $kk => $vv) {
                        if(empty($category_meta_info[$kk]['meta_description'])) {
                            $this->model_extension_module_seo->editCategoryDescription($description, $total_categories[$k]['category_id']);
                        } else {
                            if($data['seo_categories_apply'] == 1) {
                                $this->model_extension_module_seo->editCategoryDescription($description, $total_categories[$k]['category_id']);
                            } else {
                                break;
                            }
                        }
                    }
                }
                break;


            case "seo_products_keywords":
                $total_products = $this->model_catalog_product->getProducts();
                foreach ($total_products as $k => $v) {
                    $keywords = $data[$key];

                    $product_id = $total_products[$k]['product_id'];
                    $product_name = $total_products[$k]['name'];
                    $product_meta_keyword = $total_products[$k]['meta_keyword'];
                    $manufacturer_name = $this->model_extension_module_seo->getProductManufacturer($total_products[$k]['manufacturer_id']);
                    $product_cats_id = $this->model_catalog_product->getProductCategories($total_products[$k]['product_id']);

                    $product_cats_name = array();
                    foreach ($product_cats_id as $kk => $vv) {
                        $product_cats_name[$kk] = $this->model_extension_module_seo->getCategoryName($product_cats_id[$kk]);
                    }
                    $product_cats_name = implode(' ', $product_cats_name);

                    if ($data['seo_products_use_more'] == 0) {
                        $keywords = $this->generateProductsKeywords($keywords, $product_name, NULL, NULL);
                    } else if ($data['seo_products_use_more'] == 1) {
                        $keywords = $this->generateProductsKeywords($keywords, $product_name, $manufacturer_name, NULL);
                    } else if ($data['seo_products_use_more'] == 2) {
                        $keywords = $this->generateProductsKeywords($keywords, $product_name, NULL, $product_cats_name);
                    } else {
                        $keywords = $this->generateProductsKeywords($keywords, $product_name, $manufacturer_name, $product_cats_name);
                    }

                    if(empty($product_meta_keyword)) {
                        $this->model_extension_module_seo->editProductKeywords($keywords, $product_id);
                    } else {
                        if($data['seo_products_apply'] == 1) {
                            $this->model_extension_module_seo->editProductKeywords($keywords, $product_id);
                        } else {
                            break;
                        }
                    }

                }

                break;


            case "seo_products_description":
                $total_products = $this->model_catalog_product->getProducts();
                foreach ($total_products as $k => $v) {
                    $description = $data[$key];

                    $product_meta_description = $total_products[$k]['meta_description'];

                    $product_id = $total_products[$k]['product_id'];
                    $product_price = $total_products[$k]['price'];
                    $product_price = $this->currency->format($this->tax->calculate($product_price, $total_products[$k]['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $product_name = $total_products[$k]['name'];
                    $manufacturer_name = $this->model_extension_module_seo->getProductManufacturer($total_products[$k]['manufacturer_id']);
                    $product_cats_id = $this->model_catalog_product->getProductCategories($total_products[$k]['product_id']);

                    $product_cats_name = array();
                    foreach ($product_cats_id as $kk => $vv) {
                        $product_cats_name[$kk] = $this->model_extension_module_seo->getCategoryName($product_cats_id[$kk]);
                    }
                    $product_cats_name = implode(' ', $product_cats_name);

                    $description .= ' - ';
                    if(isset($manufacturer_name)) {
                        $description .= $manufacturer_name . ' ';
                    } else if(!isset($manufacturer_name) && isset($product_cats_name)) {
                        $description .= $product_cats_name . ' ';
                    }
                    $description .= $product_name . ' ' . $product_price;

                    if(empty($product_meta_description)) {
                        $this->model_extension_module_seo->editProductDescription($description, $product_id);
                    } else {
                        if($data['seo_products_apply'] == 1) {
                            $this->model_extension_module_seo->editProductDescription($description, $product_id);
                        } else {
                            break;
                        }
                    }

                }
                break;


            case "seo_articles_refresh_keyword":
                if($data[$key] == 1) {
                    $total_articles = $this->model_catalog_information->getInformations();

                    foreach ($total_articles as $k => $v) {
                        $article_id = $total_articles[$k]['information_id'];
                        $article_meta_keyword = $total_articles[$k]['meta_keyword'];

                        $keywords = trim(mb_strtolower($total_articles[$k]['title']));

                        if($data['seo_articles_divide'] == 1) {
                            $addictional_keywords = explode(' ', $keywords);
                            $addictional_keywords_length = count($addictional_keywords);
                            $addictional_keywords = implode(', ', $addictional_keywords);
                            if($addictional_keywords_length > 1) {
                                $keywords .= ', ' . $addictional_keywords;
                            }
                        }

                        if ($data['seo_articles_apply'] == 1) {
                            $this->model_extension_module_seo->editInformationKeywords($keywords, $article_id);
                        } else {
                            if (!isset($article_meta_keyword)) {
                                $this->model_extension_module_seo->editInformationKeywords($keywords, $article_id);
                            }
                        }

                    }
                } else {
                    break;
                }

                break;


            case "seo_articles_refresh_descr":
                if($data[$key] == 1) {
                    $total_articles = $this->model_catalog_information->getInformations();

                    foreach($total_articles as $k => $v) {
                        $article_id = $total_articles[$k]['information_id'];
                        $article_meta_description = $total_articles[$k]['meta_description'];


                        $description = $total_articles[$k]['description'];
                        $description = $total_articles[$k]['title'] . ' -' . $description;
                        $description = preg_replace('/\s+/', ' ', $description);
                        $description = html_entity_decode($description);
                        $description = strip_tags($description);
                        $description = trim($description);
                        $description = substr($description, 0, 67);
                        $description = rtrim($description, "!,.-");
                        $description = substr($description, 0, strrpos($description, ' '));
                        $description .= '...';

                        if($data['seo_articles_apply'] == 1) {
                            $this->model_extension_module_seo->editInformationDescription($description, $article_id);
                        } else {
                            if(!isset($article_meta_description)) {
                                $this->model_extension_module_seo->editInformationDescription($description, $article_id);
                            }
                        }

                    }
                } else {
                    break;
                }

                break;


            default:
                break;
        }
    }

    private function concatKeywords($user_keywords, $product_name, $word) {
        $keywords = '';
        $exploded_user_keywords =  explode(',', $user_keywords);

        foreach ($exploded_user_keywords as $key => $value) {
            if(count($exploded_user_keywords) - 1 != $key) {
                $keywords .= $value . ' ';
                if (isset($word)) {
                    $keywords .= $word . ' ';
                }
                $keywords .= $product_name . ',';
            } else {
                $keywords .= $value . ' ';
                if (isset($word)) {
                    $keywords .= $word . ' ';
                }
                $keywords .= $product_name;
            }
        }

        return trim($keywords);
    }

    private function generateProductsKeywords($user_keywords, $product_name, $brand, $cat_name) {
        $keywords = '';

        if(isset($brand) && isset($cat_name)) {
            $keywords .= $this->concatKeywords($user_keywords, $product_name, $brand);
            $keywords .= ', ';
            $keywords .= $this->concatKeywords($user_keywords, $product_name, $cat_name);

            return trim($keywords);
        }

        if(isset($brand)) {
            $keywords .= $this->concatKeywords($user_keywords, $product_name, $brand);
            return trim($keywords);
        }

        if(isset($cat_name)) {
            $keywords .= $this->concatKeywords($user_keywords, $product_name, $cat_name);
            return trim($keywords);
        }

        $keywords .= $this->concatKeywords($user_keywords, $product_name, '');
        return trim($keywords);
    }

    private function breadcrumbs () {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->createUrl('common/home'),
            'separator' => false
        );
        $breadcrumbs[] = array(
            'text'      => $this->language->get('text_extension'),
            'href'      => $this->createUrl('extension/extension'),
            'separator' => ' :: '
        );
        $breadcrumbs[] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->createUrl('extension/module/seo'),
            'separator' => ' :: '
        );

        return $breadcrumbs;
    }
}