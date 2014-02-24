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

    $hash = md5("ucresizeimg" // salt
        .$arParams["INPUT_FILE_PATH"].$arParams["INPUT_IMAGE_ID"]
        .$arParams["WIDTH"].$arParams["HEIGHT"]
        .$arParams["RESIZE_TYPE"].$arParams["CROP_POS_X"].$arParams["CROP_POS_Y"]
        .$arParams["KEEP_SIZE"].$arParams["FILL_COLOR"].$arParams["JPEG_OUTPUT"]
        .$arParams["JPEG_QUALITY"].$arParams["PNG_QUALITY"]
    );

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
                $arResult["ERROR"] = GetMessage("E_CANNOT_CREATE_FOLDER", array("#PATH#" => $curDir));
                return $abort($this);
            }
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
        || intval($arParams["WIDTH"])  != $arParams["WIDTH"]
        || intval($arParams["HEIGHT"]) != $arParams["HEIGHT"] ){
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
          case "FILL": case "FILL_NON_TRANSPARENT":
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
    $arResult["SRC"] .= $prefix . $hash .".". $srcType;
    $arResult["WIDTH"] = $outWidth;
    $arResult["HEIGHT"] = $outHeight;
    $arResult["OFFSET_X"] = $offsetX;
    $arResult["OFFSET_Y"] = $offsetY;

    $outImg = ImageCreateTrueColor($outWidth, $outHeight);
    //ImageFill($outputImage, 0, 0, ImageColorAllocate($outputImage,
    //    $resizeParams["FILL_COLOR"]["R"], $resizeParams["FILL_COLOR"]["G"], $resizeParams["FILL_COLOR"]["B"]));

    $inImg = null;
    switch (strtoupper($srcPathInfo["extension"])) {
    case "JPEG": case "JPG":
        $inImg = ImageCreateFromJPEG($rootPath ."/". $arResult["SOURCE_IMAGE"]["SRC"]);
        break;

    case "PNG":
        $inImg = ImageCreateFromPNG($rootPath ."/". $arResult["SOURCE_IMAGE"]["SRC"]);
        ImageAlphaBlending($outImg, false);
        ImageSaveAlpha($outImg, true);
        break;
    }

    ImageCopyResampled(
        $outImg, $inImg, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight,
        $arResult["SOURCE_IMAGE"]["WIDTH"], $arResult["SOURCE_IMAGE"]["HEIGHT"]
    );

    switch (strtoupper($srcPathInfo["extension"])) {
    case "JPEG": case "JPG":
        ImageJPEG($outImg, $rootPath ."/". $arResult["SRC"], 100); // TODO: quality
        break;

    case "PNG":
        ImagePNG($outImg, $rootPath ."/". $arResult["SRC"], 9); // 9 is max compression
        break;
    }

    ImageDestroy($inImg);
    ImageDestroy($outImg);

    $this->IncludeComponentTemplate();

}
