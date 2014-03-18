<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\build\controllers;

use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\FileHelper;

/**
 * Creates a class map for the core Yii classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ClassmapController extends Controller
{
    public $defaultAction = 'create';

    /**
     * Creates a class map for the core Yii classes.
     * @param string $root    the root path of Yii framework. Defaults to YII_PATH.
     * @param string $mapFile the file to contain the class map. Defaults to YII_PATH . '/classes.php'.
     */
    public function actionCreate($root = null, $mapFile = null)
    {
        if ($root === null) {
            $root = YII_PATH;
        }
        $root = FileHelper::normalizePath($root);
        if ($mapFile === null) {
            $mapFile = YII_PATH . '/classes.php';
        }
        $options = [
            'filter' => function ($path) {
                if (is_file($path)) {
                    $file = basename($path);
                    if ($file[0] < 'A' || $file[0] > 'Z') {
                        return false;
                    }
                }

                return null;
            },
            'only' => ['*.php'],
            'except' => [
                '/Yii.php',
                '/BaseYii.php',
                '/console/',
            ],
        ];
        $files = FileHelper::findFiles($root, $options);
        $map = [];
        foreach ($files as $file) {
            if (strpos($file, $root) !== 0) {
                throw new Exception("Something wrong: $file\n");
            }
            $path = str_replace('\\', '/', substr($file, strlen($root)));
            $map[$path] = "\t'yii" . substr(str_replace('/', '\\', $path), 0, -4) . "' => YII_PATH . '$path',";
        }
        ksort($map);
        $map = implode("\n", $map);
        $output = <<<EOD
<?php
/**
 * Yii core class map.
 *
 * This file is automatically generated by the "build classmap" command under the "build" folder.
 * Do not modify it directly.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

return [
$map
];

EOD;
        if (is_file($mapFile) && file_get_contents($mapFile) === $output) {
            echo "Nothing changed.\n";
        } else {
            file_put_contents($mapFile, $output);
            echo "Class map saved in $mapFile\n";
        }
    }
}
