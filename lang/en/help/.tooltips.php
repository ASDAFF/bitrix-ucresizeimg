<?

$MESS ["INPUT_FILE_PATH_TIP"] = "From site root";
$MESS ["INPUT_IMAGE_ID_TIP"] = "Get image by ID via Bitrix-API CFile."
    ."<br/><br/>Keep it empty for using input file path.";
$MESS ["DESCRIPTION_TIP"] = "Hash-tag #IMAGE_ID_DESCRIPTION# will be replaced to image description from Bitrix-API CFile (for image ID field).";

$MESS ["WIDTH_TIP"] = "Keep it empty for unlimited width.";
$MESS ["HEIGHT_TIP"] = "Keep it empty for unlimited height.";
$MESS ["RESIZE_TYPE_TIP"] = "‘Limit size‛ mode reduces an image size, limiting it to the specified size."
        ." If source image size is 16:9 aspect ratio and ‘Width limit‛ and ‘Height limit‛ fields values is 200×200 pixels"
            ." then resized image size will be equals 200×112 pixels."
    ."<br/></br>‘Crop‛ mode makes the specified image size and cuts off the excess parts in accord with the positioning fields values."
        ." If source image size is 16:9 aspect ratio and ‘Required width‛ and ‘Required height‛ fields values is 200×200 pixels "
            ."then image will change its size to 355×200 pixels, and excess parts will be cuts of "
            ."on left and right sides horizontally (in accord with the horizontal positioning field value) "
            ."and image size will change to 200×200 pixels.";
$MESS ["CROP_POS_X_TIP"] = "Works only when there are excess in width."
    ."<br/><br/>In ‘Left‛ mode excess will be cut off on the right side."
    ."<br/>In ‘Center‛ mode excess will be cut off on the left and right sides."
    ."<br/>In ‘Right‛ mode excess will be cut off on the left side.";
$MESS ["CROP_POS_Y_TIP"] = "Works only when there are excess in height."
    ."<br/><br/>In ‘Top‛ mode excess will be cut off on the bottom side."
    ."<br/>In ‘Center‛ mode excess will be cut off on the top and bottom sides."
    ."<br/>In ‘Bottom‛ mode excess will be cut off on the top side.";
$MESS ["KEEP_SIZE_TIP"] = "If source image size less than specified size."
    ."<br/></br>If you need to keep transparent if it possible — use alpha channel of fill color.";
$MESS ["FILL_COLOR_TIP"] = "Possible values:"
    ."<ul><li>#fff</li>"
    ."<li>#ffffff</li>"
    ."<li>#abc</li>"
    ."<li>#aabbcc</li>"
    ."<li>rgb(255, 255, 255)</li>"
    ."<li>rgba(255, 255, 255, 1) (alpha-channel only for PNG)</li>"
    ."<li>rgba(255, 255, 255, 0.5) (alpha-channel only for PNG)</li></ul>";

$MESS ["PNG_QUALITY_TIP"] = "After conversion to JPEG";
$MESS ["FILE_PREFIX_TIP"] = "Typically, the output files have names like this (without prefix):"
    ."<ul><li>d41d8cd98f00b204e9800998ecf8427e.jpg</li>"
    ."<li>d41d8cd98f00b204e9800998ecf8427e.png</li></ul>"
    ."But you can set prefix that will be put in start of file name."
    ." You also can use this hash-tags for auto-replacing:"
    ."<ul><li>#INPUT_FILE_NAME# — will replaced to «Red_Panda»</li>"
    ."<li>#INPUT_FILE_EXT# — will replaced to «.png»</li></ul>"
    ."If prefix is «#INPUT_FILE_NAME#_» then output file will be have name: "
    ."«Red_Panda_d41d8cd98f00b204e9800998ecf8427e.png».";

$MESS ["UNIQUE_SALT_TIP"] = "It is strongly recommended to always use this field "
    ."to avoid the accumulation of unused images and potential memory overflow.";
$MESS ["CACHE_ENABLE_TIP"] = "It makes sense to disable caching for testing or if (auto-)caching enabled for parent component.";
