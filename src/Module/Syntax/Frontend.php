<?php

namespace ArshWell\Monolith\Module\Syntax;

final class Frontend {

    static function breadcrumbs (array $breadcrumbs): array {
        return $breadcrumbs;
    }

    static function actions (array $actions): array {
        foreach ($actions as $key => $action) {
            foreach ($action as $category => $attributes) {
                foreach ($attributes as $attr => $value) {
                    $actions[$key][$category][$attr] = ("ArshWell\Monolith\Module\Syntax\Frontend\Action\\{$category}")::{$attr}($value);
                }
            }
        }

        return $actions;
    }

    static function features (array $features): array {
        foreach ($features as $key => $feature) {
            foreach ($feature as $category => $attributes) {
                foreach ($attributes as $attr => $value) {
                    $features[$key][$category][$attr] = ("ArshWell\Monolith\Module\Syntax\Frontend\Feature\\{$category}")::{$attr}($value);
                }
            }
        }

        return $features;
    }

    static function fields (array $fields): array {
        foreach ($fields as $key => $field) {
            foreach ($field as $category => $attributes) {
                foreach ($attributes as $attr => $value) {
                    $fields[$key][$category][$attr] = ("ArshWell\Monolith\Module\Syntax\Frontend\Field\\{$category}")::{$attr}($value);
                }
            }
        }

        return $fields;
    }
}
