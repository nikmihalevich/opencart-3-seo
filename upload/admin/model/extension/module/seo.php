<?php

class ModelExtensionModuleSEO extends Model {

    const SETTINGS_GROUP = 'seo';
    const SETTINGS_GROUP_KEY = 'seo_data';

    public function defaultSettingsItem () {
        return array(
            // categories
            self::SETTINGS_GROUP . '_categories_apply'          => '',
            self::SETTINGS_GROUP . '_categories_keywords'       => '',
            self::SETTINGS_GROUP . '_categories_description'    => '',

            // products
            self::SETTINGS_GROUP . '_products_apply'            => '',
            self::SETTINGS_GROUP . '_products_use_more'         => '',
            self::SETTINGS_GROUP . '_products_keywords'         => '',
            self::SETTINGS_GROUP . '_products_description'      => '',

            // articles
            self::SETTINGS_GROUP . '_articles_apply'            => '',
            self::SETTINGS_GROUP . '_articles_divide'           => '',
            self::SETTINGS_GROUP . '_articles_refresh_keyword'  => '',
            self::SETTINGS_GROUP . '_articles_refresh_descr'    => '',
        );
    }

    public function defaultSettings () {
        $default = array();
        $default = $this->defaultSettingsItem();
        return $default;
    }

    public function install () {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(self::SETTINGS_GROUP, array(
            self::SETTINGS_GROUP_KEY => $this->defaultSettings()
        ));
    }

    public function uninstall () {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting(self::SETTINGS_GROUP);
    }

    public function getSettings () {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting(self::SETTINGS_GROUP);

        if (is_null($settings) || !isset($settings[self::SETTINGS_GROUP_KEY])) {
            return $this->defaultSettings();
        }
        else {
            return array_replace_recursive($this->defaultSettings(), $settings[self::SETTINGS_GROUP_KEY]);
        }
    }

    public function editCategoryKeywords($meta_keywords, $category_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "category_description SET meta_keyword = '" . $this->db->escape($meta_keywords) . "' WHERE category_id = '" . (int)$category_id . "'");
    }

    public function editCategoryDescription($meta_description, $category_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "category_description SET meta_description = '" . $this->db->escape($meta_description) . "' WHERE category_id = '" . (int)$category_id . "'");
    }

    public function getProductManufacturer($manufacturer_id) {
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

        return isset($query->row['name']) ? $query->row['name'] : NULL;
    }

    public function getCategoryName($category_id) {
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "'");

        return isset($query->row['name']) ? $query->row['name'] : NULL;
    }

    public function editProductKeywords($meta_keywords, $product_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_description SET meta_keyword = '" . $this->db->escape($meta_keywords) . "' WHERE product_id = '" . (int)$product_id . "'");
    }

    public function editProductDescription($meta_description, $product_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_description SET meta_description = '" . $this->db->escape($meta_description) . "' WHERE product_id = '" . (int)$product_id . "'");
    }

    public function editInformationKeywords($meta_keywords, $information_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "information_description SET meta_keyword = '" . $this->db->escape($meta_keywords) . "' WHERE information_id = '" . (int)$information_id . "'");
    }

    public function editInformationDescription($meta_description, $information_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "information_description SET meta_description = '" . $this->db->escape($meta_description) . "' WHERE information_id = '" . (int)$information_id . "'");
    }

    public function saveSettings ($settings) {
        $this->load->model('setting/setting');
        $settings = array_replace_recursive($this->defaultSettings(), $settings);
        $this->model_setting_setting->editSettingValue(self::SETTINGS_GROUP, self::SETTINGS_GROUP_KEY, $settings);
    }
}