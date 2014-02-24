<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arParams["CACHE_ENABLE"] == "N"
|| $this->StartResultCache($arParams["CACHE_INTERVAL"])) {

    $abort = function (&$obj) {
        $obj->AbortResultCache();
        $obj->IncludeComponentTemplate();
    };

    $absPath = function ($path) {
        if ($path[0] != "/") $path = "/" . $path;

        /** relative path (site root) */
        $rootPath = explode("/", dirname(__FILE__));
        $rootPath = array_slice($rootPath, 0, -4);
        $rootPath = implode("/", $rootPath);

        return $rootPath . $path;
    };

    $arResult["ERROR"] = null;
    $supportedFormats = array("JPEG", "PNG");

    $hash = null;
    if (!empty($arParams["UNIQUE_SALT"])) {
        $hash = md5("ucresizeimg" . $arParams["UNIQUE_SALT"]);
    } else {
        $hash = md5("ucresizeimg" // salt
            .$arParams["INPUT_FILE_PATH"].$arParams["INPUT_IMAGE_ID"]
            .$arParams["WIDTH"].$arParams["HEIGHT"]
            .$arParams["RESIZE_TYPE"].$arParams["CROP_POS_X"].$arParams["CROP_POS_Y"]
            .$arParams["KEEP_SIZE"].$arParams["FILL_COLOR"].$arParams["JPEG_OUTPUT"]
            .$arParams["JPEG_QUALITY"].$arParams["PNG_QUALITY"]
        );
    }

    $arResult["SOURCE_IMAGE"] = array();

    if (!empty($arParams["INPUT_IMAGE_ID"])) {
        $rsFile = CFile::GetByID($arParams["INPUT_IMAGE_ID"]);
        $arFile = $rsFile->GetNext();
        $arResult["SOURCE_IMAGE"]["SRC"] = CFile::GetPath($arParams["INPUT_IMAGE_ID"]);
        $arResult["SOURCE_IMAGE"]["WIDTH"] = $arFile["WIDTH"];
        $arResult["SOURCE_IMAGE"]["HEIGHT"] = $arFile["HEIGHT"];
        $arResult["DESCRIPTION"] = str_replace(
            "#IMAGE_ID_DESCRIPTION#", $arFile["DESCRIPTION"], $arParams["DESCRIPTION"]);
    } else {
        if (empty($arParams["INPUT_FILE_PATH"])) {
            $arResult["ERROR"] = GetMessage("E_EMPTY_INPUT_FILE");
            return $abort($this);
        } else {
            $arResult["SOURCE_IMAGE"]["SRC"] = $arParams["INPUT_FILE_PATH"];
            if (!$inputFileSize = GetImageSize($absPath($arResult["SOURCE_IMAGE"]["SRC"]))) {
                $arResult["ERROR"] = GetMessage("E_GET_IMG_FILE_SIZE");
                return $abort($this);
            }
            $arResult["SOURCE_IMAGE"]["WIDTH"] = $inputFileSize[0];
            $arResult["SOURCE_IMAGE"]["HEIGHT"] = $inputFileSize[1];
            $arResult["DESCRIPTION"] = str_replace(
                "#IMAGE_ID_DESCRIPTION#", "", $arParams["DESCRIPTION"]);
        }
    }

    $srcPathInfo = pathinfo($arResult["SOURCE_IMAGE"]["SRC"]);
    $srcType = strtolower($srcPathInfo["extension"]);

    switch (strtoupper($srcPathInfo["extension"])) {
      case "JPEG": case "JPG": case "PNG": break;
      default:
        $arResult["ERROR"] = GetMessage("E_UNSUPPORTED_TYPE");
        return $abort($this);
    }

    if (empty($arParams["RESIZED_PATH"])) {
        $arResult["ERROR"] = GetMessage("E_EMPTY_RESIZED_PATH");
        return $abort($this);
    }

    /** relative path (site root) */
    $rootPath = explode("/", dirname(__FILE__));
    $rootPath = array_slice($rootPath, 0, -4);
    $rootPath = implode("/", $rootPath);

    /** create resized dir if not exists (with parents) */
    $path = explode("/", $arParams["RESIZED_PATH"]);
    $curDir = $rootPath;
    for ($i=1; $i<count($path); $i++) {
        if (!empty($path[$i]) && $path[$i] != '.') {
            $curDir .= "/" . $path[$i];
            if (!is_dir($curDir) && !@mkdir($curDir, 0755)) {
                $arResult["ERROR"] = GetMessage("E_CANNOT_CREATE_DIR", array("#PATH#" => $curDir));
                return $abort($this);
            }
        }
    }

    /** cleanup previous resized files */
    if (!empty($arParams["UNIQUE_SALT"])) {
        $handle = null;
        $curDir = $rootPath ."/". $arParams["RESIZED_PATH"];
        if ($handle = opendir($curDir)) {
            while (false !== ($entry = readdir($handle))) {
                if (!is_file($curDir ."/". $entry)) continue;
                if (preg_match("/^.*".$hash."\.(jpeg|jpg|png)$/", $entry, $matches)) {
                    @unlink($curDir ."/". $entry);
                }
            }
        } else {
            $arResult["ERROR"] = GetMessage("E_CANNOT_OPEN_DIR", array("#PATH#" => $curDir));
            return $abort($this);
        }
    }

    $newWidth = 0;
    $newHeight = 0;
    $outWidth = 0;
    $outHeight = 0;
    $offsetX = 0;
    $offsetY = 0;

    if ($arParams["RESIZE_TYPE"] == "CROP") {
        if( empty($arParams["WIDTH"]) || empty($arParams["HEIGHT"])
        || (string)intval($arParams["WIDTH"])  != (string)$arParams["WIDTH"]
        || (string)intval($arParams["HEIGHT"]) != (string)$arParams["HEIGHT"] ){
            $arResult["ERROR"] = GetMessage("E_CROP_WH_INCORRECT");
            return $abort($this);
        }

        $getOffset = function ($full, $limit, $posMode) {
            $offset = 0;
            switch ($posMode) {
              case "TOP": case "LEFT": break;
              case "BOTTOM": case "RIGHT":
                $offset = -($full - $limit);
                break;
              case "MIDDLE": case "CENTER": default:
                $offset = -(($full - $limit) / 2);
            }
            return $offset;
        };

        $doCrop = function ($src1, $src2, $out1, $out2, $posMode, $getOffset) {
            $new1 = $out1;
            $new2 = $src2 * $new1 / $src1;
            $offset = $getOffset($new2, $out2, $posMode);
            return array($new1, $new2, $offset);
        };

        $rszWidth = $arResult["SOURCE_IMAGE"]["WIDTH"] * $arParams["HEIGHT"] / $arResult["SOURCE_IMAGE"]["HEIGHT"];
        $rszHeight = $arParams["HEIGHT"];

        $crop = null;
        if ($rszWidth > $arParams["WIDTH"]) {
            $crop = $doCrop( $arResult["SOURCE_IMAGE"]["HEIGHT"], $arResult["SOURCE_IMAGE"]["WIDTH"],
                             $arParams["HEIGHT"], $arParams["WIDTH"], $arParams["CROP_POS_X"],
                             $getOffset );
            $newWidth = $crop[1];
            $newHeight = $crop[0];
            $offsetX = $crop[2];
        } else {
            $crop = $doCrop( $arResult["SOURCE_IMAGE"]["WIDTH"], $arResult["SOURCE_IMAGE"]["HEIGHT"],
                             $arParams["WIDTH"], $arParams["HEIGHT"], $arParams["CROP_POS_Y"],
                             $getOffset );
            $newWidth = $crop[0];
            $newHeight = $crop[1];
            $offsetY = $crop[2];
        }

        $outWidth = $arParams["WIDTH"];
        $outHeight = $arParams["HEIGHT"];

        switch ($arParams["KEEP_SIZE"]) {
          case "NO":
            if( $arParams["WIDTH"]  > $arResult["SOURCE_IMAGE"]["WIDTH"]
             || $arParams["HEIGHT"] > $arResult["SOURCE_IMAGE"]["HEIGHT"] ) {
                $newWidth = $arResult["SOURCE_IMAGE"]["WIDTH"];
                $newHeight = $arResult["SOURCE_IMAGE"]["HEIGHT"];

                if ($newWidth > $arParams["WIDTH"]) {
                    $outWidth = $arParams["WIDTH"];
                    $outHeight = $newHeight;
                } elseif ($newHeight > $arParams["HEIGHT"]) {
                    $outWidth = $newWidth;
                    $outHeight = $arParams["HEIGHT"];
                } else {
                    $outWidth = $newHeight;
                    $outHeight = $newHeight;
                }

                $offsetX = $getOffset($newWidth, $outWidth, $arParams["CROP_POS_X"]);
                $offsetY = $getOffset($newHeight, $outHeight, $arParams["CROP_POS_Y"]);
            }
            break;
          case "FILL":
            if( $arParams["WIDTH"]  > $arResult["SOURCE_IMAGE"]["WIDTH"]
             || $arParams["HEIGHT"] > $arResult["SOURCE_IMAGE"]["HEIGHT"] ) {
                $newWidth = $arResult["SOURCE_IMAGE"]["WIDTH"];
                $newHeight = $arResult["SOURCE_IMAGE"]["HEIGHT"];

                $outWidth = $arParams["WIDTH"];
                $outHeight = $arParams["HEIGHT"];

                $offsetX = $getOffset($newWidth, $outWidth, $arParams["CROP_POS_X"]);
                $offsetY = $getOffset($newHeight, $outHeight, $arParams["CROP_POS_Y"]);
            }
            break;
          case "EXTEND": default:
        }
    } elseif ($arParams["RESIZE_TYPE"] == "LIMIT") {
        if (!empty($arParams["WIDTH"]) && (string)intval($arParams["WIDTH"]) != (string)$arParams["WIDTH"]) {
            $arResult["ERROR"] = GetMessage("E_LIMIT_WH_INCORRECT");
            return $abort($this);
        }

        if (!empty($arParams["HEIGHT"]) && (string)intval($arParams["HEIGHT"]) != (string)$arParams["HEIGHT"]) {
            $arResult["ERROR"] = GetMessage("E_LIMIT_WH_INCORRECT");
            return $abort($this);
        }

        if (!empty($arParams["WIDTH"])) {
            $newWidth = $arParams["WIDTH"];
            $newHeight = $arResult["SOURCE_IMAGE"]["HEIGHT"] * $newWidth / $arResult["SOURCE_IMAGE"]["WIDTH"];
        }

        if ( !empty($arParams["HEIGHT"]) && (empty($arParams["WIDTH"]) || $newHeight > $arParams["HEIGHT"]) ) {
            $newHeight = $arParams["HEIGHT"];
            $newWidth = $arResult["SOURCE_IMAGE"]["WIDTH"] * $newHeight / $arResult["SOURCE_IMAGE"]["HEIGHT"];
        }

        if (empty($arParams["WIDTH"]) && empty($arParams["HEIGHT"])) {
            $newWidth = $arResult["SOURCE_IMAGE"]["WIDTH"];
            $newHeight = $arResult["SOURCE_IMAGE"]["HEIGHT"];
        }

        if( $newWidth  > $arResult["SOURCE_IMAGE"]["WIDTH"]
        ||  $newHeight > $arResult["SOURCE_IMAGE"]["HEIGHT"] ) {
            $newWidth = $arResult["SOURCE_IMAGE"]["WIDTH"];
            $newHeight = $arResult["SOURCE_IMAGE"]["HEIGHT"];
        }

        $outWidth = $newWidth;
        $outHeight = $newHeight;
    } else {
        $arResult["ERROR"] = GetMessage("E_UNSUPPORTED_RESIZE_TYPE");
        return $abort($this);
    }

    $newWidth = round($newWidth);
    $newHeight = round($newHeight);
    $offsetX = round($offsetX);
    $offsetY = round($offsetY);

    $arResult["SRC"] = $arParams["RESIZED_PATH"];
    if ($arResult["SRC"][strlen($arResult["SRC"])-1] != "/") {
        $arResult["SRC"] .= "/";
    }
    $prefix = str_replace("#INPUT_FILE_NAME#", $srcPathInfo["filename"], $arParams["FILE_PREFIX"]);
    if ($arParams["JPEG_OUTPUT"] == "CONV_PNG_DIFF_Q"
    || $arParams["JPEG_OUTPUT"] == "CONV_PNG_SET_Q") {
        $arResult["SRC"] .= $prefix . $hash .".jpeg";
    } else {
        $arResult["SRC"] .= $prefix . $hash .".". $srcType;
    }
    $arResult["WIDTH"] = $outWidth;
    $arResult["HEIGHT"] = $outHeight;
    $arResult["OFFSET_X"] = $offsetX;
    $arResult["OFFSET_Y"] = $offsetY;

    $outImg = ImageCreateTrueColor($outWidth, $outHeight);

    $R = null; $G = null; $B = null; $A = null; $color = null; $hasColor = true;
    $color = preg_replace("/\s+/", "", $arParams["FILL_COLOR"]);
    $color = strtolower($color);

    if (!empty($color)) {
        if (preg_match("/^\#([\d\w])([\d\w])([\d\w])$/", $color, $matches)) {
            $R = $matches[1]; $R = hexdec($R.$R); if ($R < 0) $R = 0; if ($R > 255) $R = 255;
            $G = $matches[2]; $G = hexdec($G.$G); if ($G < 0) $G = 0; if ($G > 255) $G = 255;
            $B = $matches[3]; $B = hexdec($B.$B); if ($B < 0) $B = 0; if ($B > 255) $B = 255;
            $A = 0;
        } elseif (preg_match("/^\#([\d\w]{2})([\d\w]{2})([\d\w]{2})$/", $color, $matches)) {
            $R = hexdec($matches[1]); if ($R < 0) $R = 0; if ($R > 255) $R = 255;
            $G = hexdec($matches[2]); if ($G < 0) $G = 0; if ($G > 255) $G = 255;
            $B = hexdec($matches[3]); if ($B < 0) $B = 0; if ($B > 255) $B = 255;
            $A = 0;
        } elseif (preg_match("/^rgb\(([\d]{1,3}),([\d]{1,3}),([\d]{1,3})\)$/", $color, $matches)) {
            $R = $matches[1]; if ($R < 0) $R = 0; if ($R > 255) $R = 255;
            $G = $matches[2]; if ($G < 0) $G = 0; if ($G > 255) $G = 255;
            $B = $matches[3]; if ($B < 0) $B = 0; if ($B > 255) $B = 255;
            $A = 0;
        } elseif (preg_match("/^rgba\(([\d]{1,3}),([\d]{1,3}),([\d]{1,3}),([\d]{1}(\.[\d]+)?)\)$/", $color, $matches)) {
            $R = $matches[1]; if ($R < 0) $R = 0; if ($R > 255) $R = 255;
            $G = $matches[2]; if ($G < 0) $G = 0; if ($G > 255) $G = 255;
            $B = $matches[3]; if ($B < 0) $B = 0; if ($B > 255) $B = 255;

            $A = (float)$matches[4];
            $A = round($A * 127 / 1);
            if ($A < 0) $A = 0; if ($A > 127) $A = 127;
            $A = 127 - $A;
        } else {
            $arResult["ERROR"] = GetMessage("E_INCORRECT_FILL_COLOR_VALUE", array(
                "#VALUE#" => $color,
            ));
            return $abort($this);
        }
    } else {
        $hasColor = false;
    }
    $color = null;

    $inImg = null;
    switch (strtoupper($srcPathInfo["extension"])) {
      case "JPEG": case "JPG":
        $inImg = ImageCreateFromJPEG($rootPath ."/". $arResult["SOURCE_IMAGE"]["SRC"]);
        if ($hasColor) {
            $color = ImageColorAllocate($outImg, $R, $G, $B);
        }
        break;

      case "PNG":
        $inImg = ImageCreateFromPNG($rootPath ."/". $arResult["SOURCE_IMAGE"]["SRC"]);
        ImageAlphaBlending($outImg, false);
        ImageSaveAlpha($outImg, true);
        if ($hasColor) {
            if ($arParams["JPEG_OUTPUT"] == "CONV_PNG_DIFF_Q"
            || $arParams["JPEG_OUTPUT"] == "CONV_PNG_SET_Q") {
                $color = ImageColorAllocate($outImg, $R, $G, $B);
            } else {
                $color = ImageColorAllocateAlpha($outImg, $R, $G, $B, $A);
            }
        }
        break;
    }

    if( $hasColor && ($arParams["FILL_ALWAYS"] == "Y"
    || ($arParams["RESIZE_TYPE"] == "CROP" && $arParams["KEEP_SIZE"] == "FILL") ) ) {
        ImageFill($outImg, 0, 0, $color);
    }

    ImageCopyResampled(
        $outImg, $inImg, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight,
        $arResult["SOURCE_IMAGE"]["WIDTH"], $arResult["SOURCE_IMAGE"]["HEIGHT"]
    );

    switch (strtoupper($srcPathInfo["extension"])) {
      case "JPEG": case "JPG":
        ImageJPEG($outImg, $rootPath ."/". $arResult["SRC"], $arParams["JPEG_QUALITY"]);
        break;

      case "PNG":
        switch ($arParams["JPEG_OUTPUT"]) {
          case "CONV_PNG_DIFF_Q":
            ImageJPEG($outImg, $rootPath ."/". $arResult["SRC"], $arParams["PNG_QUALITY"]);
            break;
          case "CONV_PNG_SET_Q":
            ImageJPEG($outImg, $rootPath ."/". $arResult["SRC"], $arParams["JPEG_QUALITY"]);
            break;
          default:
            ImagePNG($outImg, $rootPath ."/". $arResult["SRC"], 9); // 9 is max compression
        }
        break;
    }

    ImageDestroy($inImg);
    ImageDestroy($outImg);

    $this->IncludeComponentTemplate();

}
