<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport2Demo\Console\Command\Product;
use FireGento\FastSimpleImport2Demo\Console\Command\AbstractImportCommand;
use Magento\ImportExport\Model\Import;
/**
 * Class TestCommand
 * @package FireGento\FastSimpleImport2\Console\Command
 *
 */
class ImportLeica extends AbstractImportCommand
{






    protected function configure()
    {
        $this->setName('fastsimpleimport2demo:products:importleica')
            ->setDescription('Import Leica Products ');

        $this->setBehavior(Import::BEHAVIOR_APPEND);
        $this->setEntityCode('catalog_product');

        parent::configure();
    }

    /**
     * @return array
     */
    protected function getEntities()
    {
        $data = [];
        /*
        for ($i = 1; $i <= 10; $i++) {
            $data[] = array(
                'sku' => 'FIREGENTO-' . $i,
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'product_websites' => 'base',
                'name' => 'FireGento Test Product ' . $i,
                'price' => '14.0000',
                'visibility' => 'Catalog, Search',
                'categories' => "Default Category/Kamerasysteme/S,Default Category/Kamerasysteme/SL,Default Category/Kamerasysteme/NEU"
                //'tax_class_name' => 'Taxable Goods',
                //'product_online' => '1',
                //'weight' => '1.0000',
                //'short_description' => NULL,
                //'description' => '',
            );
        }*/

        //Get category names
        $base_categories_json = file_get_contents("http://213.129.231.226:33080/tradewebkategorie.php");
        $sub_categories_json = file_get_contents("http://213.129.231.226:33080/tradewebukategorie.php");
        $artikel_json = file_get_contents("http://213.129.231.226:33080/tradeartikel.php");

        $base_cats = json_decode($base_categories_json, true);
        $sub_cats = json_decode($sub_categories_json, true);
        $artikel = json_decode($artikel_json, true);

        //convert array
        $base_cats_keyed = [];
        $sub_cats_keyed = [];
        foreach ($base_cats as $cat) {


            $parts = explode("/", $cat["NAME"]);
            foreach ($parts as $key => $part) {
                $parts[$key]= ucfirst(trim($part));
            }
            $name = implode("/", $parts);

            $base_cats_keyed[$cat["ID"]]["NAME"] = $name;
            //prepare the category names => trim all names, strtolower, capitalize

        }
        foreach ($sub_cats as $cat) {

            $parts = explode("/", $cat["NAME"]);
            foreach ($parts as $key => $part) {
                $parts[$key] = ucfirst(trim($part));
            }
            $name = implode("/", $parts);

            $sub_cats_keyed[$cat["ID"]]["NAME"] = $name;
            $sub_cats_keyed[$cat["ID"]]["NAMEPARENT"] = $base_cats_keyed[$cat["WEBKATEORIE_ID"]]["NAME"];
        }

        /*
        print_r($base_cats);
        print_r($sub_cats_keyed);
        */

        $key = 1;
        foreach ($artikel as $row) {

            //print_r($row);

            $import_arr = [];
            $import_arr["sku"] = $row["ARTIKELNUMMER"];
            $cat_str = "";

            //Nur Produkte importieren, die eine Webkategorie ID haben, die Sichtbarkeit wird über den Bestand gesteuert
            if ($row["WEBKATEGORIE_ID"] > 0) {
                $cat_str = "Default Category/".$base_cats_keyed[$row["WEBKATEGORIE_ID"]]["NAME"];

                if ($row["WEBUKATEGORIE_ID"] > 0) {
                    $cat_str = $cat_str. "|Default Category/".$sub_cats_keyed[$row["WEBUKATEGORIE_ID"]]["NAMEPARENT"]."/".$sub_cats_keyed[$row["WEBUKATEGORIE_ID"]]["NAME"];
                }

                $quantity = "Out of Stock";
                if ($row["BESTAND"] > 0) {
                    $quantity = "In Stock";
                }

                if (strlen($cat_str) > 255) {
                    print_r($cat_str);
                }

                //Die URL Keys die für MAGENTO erzeugt werden, werden offensichtlich aus der Bezeichnung erzeugt, wenn jedoch
                //ein Sonderzeichen (für URLS) in der Bezeichnung vorkommen entfernt Magento diesen und der Import kann nicht durchgeführt werden
                //Beispielsweise "Leica Korrektionslinse M -1", "Leica Korrektionslinse M -1,5" => dies führt zu einem Fehler
                //Wir müssen den URL Key konvertieren um diese ungültigen Zeichen zu mappen

                //Wir erstellen einen sha Hash aus der Artikelnummer und hängen ihn an den URL Key an 2 Zeichen sollten reichen

                //$name = $row["BEZEICHNUNG"] . " " . str_replace("*", "_1", $row["ARTIKELNUMMER"]);
                $hash = md5($row["ARTIKELNUMMER"]);
                $url_key = $row["BEZEICHNUNG"] . substr($hash,0,2);
                //print_r($name."\n");

                $data[] = array(
                    "sku" => $row["ARTIKELNUMMER"],
                    "attribute_set_code" => "Default",
                    "product_type" => "simple",
                    "product_websites" => "base",
                    "name" => $row["BEZEICHNUNG"],
                    "price" => $row["PREIS"],
                    "visibility" => "Catalog, Search",
                    "categories" => $cat_str,
                    "additional_attributes" => "has_options=0,is_returnable=Use config,quantity_and_stock_status=$quantity,required_options=0",
                    'tax_class_name' => 'Taxable Goods',
                    "url_key" => $url_key,
                    "qty" => $row['BESTAND']
                );
            }
        }

        /*
            print_r($base_cats);
            print_r($sub_cats);
        */
        print_r($data);

        //$data = [];
        return $data;
    }
}