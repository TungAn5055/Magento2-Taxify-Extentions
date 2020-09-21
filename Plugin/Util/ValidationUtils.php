<?php

namespace Mgroup\Taxify\Plugin\Util;


class ValidationUtils {
    public static function validateSkuWithLength40($sku){
        if (strlen($sku) > 40) {
            return  substr($sku, 0, 39);
        } else {
            return $sku;
		}
    }
}