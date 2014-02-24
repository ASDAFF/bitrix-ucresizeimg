<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** relative path (site root) */
$rootPath = explode("/", dirname(__FILE__));
$dirname = array_slice($rootPath, -4);
$rootPath = array_slice($rootPath, 0, -4);
$rootPath = implode("/", $rootPath);
$dirname = "/" . implode("/", $dirname);

$defaultInputFilePath = $dirname . "/images/Red_Panda.png";

if ($arCurrentValues["RESIZE_TYPE"] == "CROP") {
    $widthName = GetMessage("UCRESIZEIMG_F_WIDTH_CROP");
    $heightName = GetMessage("UCRESIZEIMG_F_HEIGHT_CROP");
    $hiddenCropFields = "N";
} else { // LIMIT mode
    $widthName = GetMessage("UCRESIZEIMG_F_WIDTH_LIMIT");
    $heightName = GetMessage("UCRESIZEIMG_F_HEIGHT_LIMIT");
    $hiddenCropFields = "Y";
}

if ($arCurrentValues["JPEG_OUTPUT"] == "CONV_PNG_DIFF_Q"
|| $arCurrentValues["JPEG_OUTPUT"] == "CONV_PNG_KEEP_ORIG_AND_PNG_Q") {
    $hiddenPNGQ = "N";
} else {
    $hiddenPNGQ = "Y";
}

if ($arCurrentValues["CACHE_ENABLE"] == "Y") {
    $hiddenCacheParams = "N";
} else {
    $hiddenCacheParams = "Y";
}

$uniqueSalt = "";
for ($i=0; $i<32; $i++) {
    $uniqueSalt .= mt_rand(0, 9);
}

$arComponentParameters = array(
    "GROUPS" => array(
        "INPUT_FILE" => array(
            "SORT" => 10,
            "NAME" => GetMessage("UCRESIZEIMG_G_INPUT_FILE"),
        ),
        "RESIZE_PARAMS" => array(
            "SORT" => 20,
            "NAME" => GetMessage("UCRESIZEIMG_G_RESIZE_PARAMS"),
        ),
        "OUTPUT_FILE" => array(
            "SORT" => 30,
            "NAME" => GetMessage("UCRESIZEIMG_G_OUTPUT_FILE")
        ),
        "CACHE" => array(
            "SORT" => 40,
            "NAME" => GetMessage("UCRESIZEIMG_G_CACHE"),
        ),
    ),
    "PARAMETERS" => array(

        /** input file parameters */

        "INPUT_FILE_PATH" => array(
            "PARENT" => "INPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_INPUT_FILE_PATH"),
            "TYPE" => "TEXT",
            "DEFAULT" => $defaultInputFilePath,
        ),
        "INPUT_IMAGE_ID" => array(
            "PARENT" => "INPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_INPUT_IMAGE_ID"),
            "TYPE" => "TEXT",
        ),
        "DESCRIPTION" => array(
            "PARENT" => "INPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_DESCRIPTION"),
            "TYPE" => "TEXT",
            "DEFAULT" => "#IMAGE_ID_DESCRIPTION#",
        ),

        /** resize configs */

        "WIDTH" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => $widthName,
            "TYPE" => "TEXT",
            "DEFAULT" => 100,
        ),
        "HEIGHT" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => $heightName,
            "TYPE" => "TEXT",
            "DEFAULT" => 100,
        ),
        "RESIZE_TYPE" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_RESIZE_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "LIMIT" => GetMessage("UCRESIZEIMG_F_RESIZE_TYPE_LIMIT"),
                "CROP" => GetMessage("UCRESIZEIMG_F_RESIZE_TYPE_CROP"),
            ),
            "DEFAULT" => "LIMIT",
            "REFRESH" => "Y",
        ),
        "CROP_POS_X" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_CROP_POS_X"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "LEFT" => GetMessage("UCRESIZEIMG_F_CROP_POS_X_LEFT"),
                "CENTER" => GetMessage("UCRESIZEIMG_F_CROP_POS_X_CENTER"),
                "RIGHT" => GetMessage("UCRESIZEIMG_F_CROP_POS_X_RIGHT"),
            ),
            "DEFAULT" => "CENTER",
            "HIDDEN" => $hiddenCropFields,
        ),
        "CROP_POS_Y" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_CROP_POS_Y"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "TOP" => GetMessage("UCRESIZEIMG_F_CROP_POS_Y_TOP"),
                "MIDDLE" => GetMessage("UCRESIZEIMG_F_CROP_POS_Y_MIDDLE"),
                "BOTTOM" => GetMessage("UCRESIZEIMG_F_CROP_POS_Y_BOTTOM"),
            ),
            "DEFAULT" => "MIDDLE",
            "HIDDEN" => $hiddenCropFields,
        ),
        "KEEP_SIZE" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_KEEP_SIZE"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "NO" => GetMessage("UCRESIZEIMG_F_KEEP_SIZE_NO"),
                "EXTEND" => GetMessage("UCRESIZEIMG_F_KEEP_SIZE_EXTEND"),
                "FILL" => GetMessage("UCRESIZEIMG_F_KEEP_SIZE_FILL"),
            ),
            "DEFAULT" => "NO",
            "HIDDEN" => $hiddenCropFields,
            "REFRESH" => "Y",
        ),
        "FILL_COLOR" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_FILL_COLOR"),
            "TYPE" => "TEXT",
            "DEFAULT" => "rgba(255, 255, 255, 0)",
        ),
        "FILL_ALWAYS" => array(
            "PARENT" => "RESIZE_PARAMS",
            "NAME" => GetMessage("UCRESIZEIMG_F_FILL_ALWAYS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),

        /** output file */

        "JPEG_OUTPUT" => array(
            "PARENT" => "OUTPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_JPEG_OUTPUT"),
            "TYPE" => "LIST",
            "VALUES" => array(
                "SET_Q" => GetMessage("UCRESIZEIMG_F_JPEG_OUTPUT_SET_Q"),
                "CONV_PNG_DIFF_Q" => GetMessage("UCRESIZEIMG_F_JPEG_OUTPUT_CONV_PNG_DIFF_Q"),
                "CONV_PNG_SET_Q" => GetMessage("UCRESIZEIMG_F_JPEG_OUTPUT_CONV_PNG_SET_Q"),
            ),
            "DEFAULT" => "SET_Q",
            "REFRESH" => "Y",
        ),
        "JPEG_QUALITY" => array(
            "PARENT" => "OUTPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_JPEG_QUALITY"),
            "TYPE" => "TEXT",
            "DEFAULT" => 85,
        ),
        "PNG_QUALITY" => array(
            "PARENT" => "OUTPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_PNG_QUALITY"),
            "TYPE" => "TEXT",
            "DEFAULT" => 85,
            "HIDDEN" => $hiddenPNGQ,
        ),
        "RESIZED_PATH" => array(
            "PARENT" => "OUTPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_RESIZED_PATH"),
            "TYPE" => "TEXT",
            "DEFAULT" => "/upload/ucresizeimg/",
        ),
        "FILE_PREFIX" => array(
            "PARENT" => "OUTPUT_FILE",
            "NAME" => GetMessage("UCRESIZEIMG_F_FILE_PREFIX"),
            "TYPE" => "TEXT",
            "DEFAULT" => "#INPUT_FILE_NAME#_",
        ),

        /** cache */

        "UNIQUE_SALT" => array(
            "PARENT" => "CACHE",
            "NAME" => GetMessage("UCRESIZEIMG_F_UNIQUE_SALT"),
            "TYPE" => "TEXT",
            "DEFAULT" => $uniqueSalt,
        ),
        "CACHE_ENABLE" => array(
            "PARENT" => "CACHE",
            "NAME" => GetMessage("UCRESIZEIMG_F_CACHE_ENABLE"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "REFRESH" => "Y",
        ),
        "CACHE_INTERVAL" => array(
            "PARENT" => "CACHE",
            "NAME" => GetMessage("UCRESIZEIMG_F_CACHE_TIME"),
            "TYPE" => "TEXT",
            "DEFAULT" => 3600,
            "HIDDEN" => $hiddenCacheParams,
        ),

    ),
);
