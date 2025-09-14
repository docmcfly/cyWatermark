# TYPO3 extension cyWatermark

## Change log

* 0.9.2 FIX Cached images with watermark cleaned if the categories changed form the file meta data.
* 0.9.1 FIX The minimum image edge filter ignors values with zero and with null.
* 0.9.0 UPD Add a clean mechanismus. Cached images with watermark is deleted if the a category or files meta data is changed.
* 0.8.0 UPD Add a minimum edge length configuration.
* 0.7.0 FIX Fix the database defaults *thanks to Garvin Hicking*
* 0.6.0 FIX Fix the composer file.
* 0.5.0 RMV Remove XClass api call.
* 0.4.1 FIX Fix an option text.
* 0.4.0 UPD Connect the extension settings to the watermark service.
* 0.3.0 DOC Add a readme documentation.
* 0.2.0 INI beta version
* 0.1.0 INI version
* 0.0.1 INI initial

## Watermarks for Frontend Images

This extension allows you to apply watermarks to images in Typo3.

### Features

* Watermarks can be assigned individually per image or by category.
* Watermarks can be placed in various positions on the image:
  * Top right
  * Bottom left
  * Top right
  * Center horizontally
  * Center diagonally
* Watermark size can be specified relative to the image.
* Watermarks do not modify the original image.
* Cropped image previews can be used.

### Installation

This extension allows you to apply watermarks to images in TYPO3.

### Administration

![Extension settings](Documentation/Images/configuration-extension.png)

* **In JSON format, you can specify the PHP-supported memory options for each image format here.**
  \
  Here are the default values in a pretty format:

  ```JSON
    {
    "jpeg":{
        "quality":75
    },
    "webp":{
        "quality":80
    },
    "bmp":{
        "compressed":true
    },
    "png":{
        "quality":-1,
        "filters":"NO_FILTER"
    }
    }
  ```

  *Hint:* Thif gif-format has not save options. (see:  [PHP: imagegif - Manual](https://www.php.net/manual/en/function.imagegif.php))

* **Supported mime types (comma separated)**
   \
   Here are the default values in a pretty format:

   ```Code
   image/jpeg,image/png,image/gif,image/webp,image/bmp
   ```

* **Minimum edge length (positive Integer)**
   \
   The watermark mechanism works only for images with the specified edge length:

    ```Code
   150
   ```

### Usage

1. **Preparation**
   1.1 Create a watermark. Here’s an example with a transparent background and semi-transparent text:
       ![Creating a watermark](Documentation/Images/creating-watermark.png)
   1.2 Upload it into Typo3
       ![Uploaded watermark](Documentation/Images/uploaded-watermark.png)

2. **Individual Image Settings**
   2.1 You can configure the watermark source directly on the image:
       ![Set individual watermark on the image](Documentation/Images/set-individual-watermark.png)

3. **Watermark Definition via Category (default)**
   3.1 Create a watermark category
       ![Creating a watermark category](Documentation/Images/creating-category.png)
       and select your watermark file here:
       ![Select watermark file, position, and size](Documentation/Images/set-category-watermark.png)
   3.2 Image setting – Source set to chosen categories
       When you select this source, all categories assigned to the image are scanned for watermark settings and applied.
       ![Watermark via category source](Documentation/Images/set-category-watermarksource.png)
       Note: If watermark source is set to “Category” or “No watermark,” the following settings apply:
       - Watermark position
       - Relative size
       - Watermark file
   3.3 Choose the category
       ![Choose watermark category](Documentation/Images/set-category.png)

4. **Result**
    ![Result](Documentation/Images/result.png)

## Troubleshooting

Watermarks are only stamped in on first display. If you change watermark settings on images that have already been displayed, the updates won’t appear immediately. The most radical and effective solution is to delete the processed files:

![Deleting the \_processed\_ files (last entry in the list)](Documentation/Images/workaround-remove-processed-files.png)
