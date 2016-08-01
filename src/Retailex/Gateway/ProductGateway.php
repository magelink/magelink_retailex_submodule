<?php
/**
 * Product and stockitem gateway for Retail Express
 * @category Retailex
 * @package Retailex\Gateway
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2016 LERO9 Ltd.
 * @license Commercial - All Rights Reserved
 */

namespace Retailex\Gateway;

use Entity\Update;
use Entity\Action;
use Retailex\Service\RetailexService;
use Log\Service\LogService;
use Magelink\Exception\MagelinkException;
use Magelink\Exception\SyncException;
use Magelink\Exception\NodeException;
use Magelink\Exception\GatewayException;
use Node\Entity;


class ProductGateway extends AbstractGateway
{

    const GATEWAY_ENTITY = 'product';
    const GATEWAY_ENTITY_CODE = 'p';

    protected $attributeMap = array(
        'ProductId'=>'',
        'SKU'=>'sku',
        'Code'=>'',
        'Description'=>'',
//        'BrandId'=>'',
        'SizeId'=>FALSE,
        'ColourId'=>FALSE,
//        'SeasonId'=>'',
        'ProductTypeId'=>'',
        'Freight'=>'',
        'Custom3'=>'',
        'LastUpdated'=>'',
        'StockAvailable'=>'',
        'StockOnHand'=>'',
        'StockOnOrder'=>'',
        'MatrixProduct'=>'',
        'ManageStock'=>'',
        'MasterPOSPrice'=>'price',
        'RRP'=>'msrp',
//        'DefaultPrice'=>'price',
        'DiscountedPrice'=>'special_price',
        'CustomerDiscountedPrice'=>'',
        'TaxRate'=>FALSE,
        'Taxable'=>'',
        'ChannelId'=>''
    );

    // ToDo: Move mapping to config
    /** @var array $this->colourIdMap */
    protected static $colourById = array(382=>'10K/Diamond', 383=>'10K/Emerald', 384=>'10K/Ruby', 387=>'10K/Silver/Dia',
        386=>'10K/Silver/Ruby', 479=>'14ct Gold', 385=>'18K', 349=>'9ct Gold', 102=>'Alabaster', 1560=>'Alligator',
        228=>'Aniseed', 448=>'Anthracite', 121=>'Army', 301=>'Ash', 1510=>'Ashes', 375=>'Ashphalt/Tarseal', 442=>'Ballet',
        423=>'Basic Black', 1551=>'Basic Grey', 1493=>'Basic Navy', 103=>'Beige', 5=>'Black', 1532=>'Black Angel',
        307=>'Black Croc', 321=>'Black Diamond', 434=>'Black Emblem', 419=>'Black Eyelet', 1545=>'Black Foil',
        446=>'Black HAHA', 308=>'Black Magic', 264=>'Black Marle', 1530=>'Black Mix', 303=>'Black Pony', 453=>'Black Putty',
        342=>'Black Russian', 406=>'Black Scrub', 245=>'Black Sheep', 392=>'Black Spots', 1480=>'Black Stripe',
        309=>'Black Twill', 1491=>'Black V', 428=>'Black Veil', 1527=>'Black/Almond', 150=>'Black/Black', 274=>'Black/Blue',
        480=>'Black/Blush', 196=>'Black/Brass', 1520=>'Black/Brown', 183=>'Black/Burg.Gingham', 195=>'Black/Burgundy',
        184=>'Black/Char.Lurex', 185=>'Black/Charcoal', 62=>'Black/Check', 371=>'Black/Cream', 30=>'Black/Dark',
        151=>'Black/Fluoro', 278=>'Black/Glow', 284=>'Black/Gold', 1484=>'Black/Gothic', 56=>'Black/Green', 177=>'Black/Grey',
        43=>'Black/Ink', 29=>'Black/Light', 1488=>'Black/Lurex', 1481=>'Black/Milk', 280=>'Black/Multi', 397=>'Black/Natural',
        407=>'Black/Navy', 441=>'Black/Nickel', 34=>'Black/Org', 273=>'Black/Pearl', 152=>'Black/Pink', 1487=>'Black/Pink Print',
        194=>'Black/Pitch', 182=>'Black/Pitch Tartan', 206=>'Black/Plum', 159=>'Black/Pop', 285=>'Black/Poppy',
        362=>'Black/Print', 318=>'Black/Pumice', 281=>'Black/Purple', 1489=>'Black/Putty', 279=>'Black/Red', 275=>'Black/Silver',
        473=>'Black/Stripe', 192=>'Black/Tartan', 157=>'Black/Violet', 1483=>'Black/Wallpaper', 33=>'Black/White',
        283=>'Black/Yellow', 1561=>'Blackadder', 229=>'Blackball', 1544=>'Blackbird', 214=>'Blackboard', 224=>'Blackcheck',
        233=>'Blacken', 211=>'Blackfelt', 223=>'Blackfelt/Leather', 1492=>'Blackfleece', 429=>'Blacklawn', 259=>'Blackout',
        212=>'Blackpool', 431=>'Blacksand', 230=>'Blackstretch', 216=>'Blacksuit', 236=>'Blackwash', 225=>'Blackwax',
        1499=>'Blackwood', 282=>'BlackYellow', 104=>'Bleach', 346=>'Blk Rhodium', 234=>'Blk/Rattlesnake', 343=>'Blood',
        99=>'Blood Stone', 77=>'Blu Chk', 3=>'Blue', 26=>'Blue Logo', 84=>'Blue Mix', 352=>'Blue Slate', 355=>'Blue Slate/Black',
        394=>'Blue Spot', 1515=>'Blue Stripes', 25=>'Blue Tartan', 351=>'Blue/White', 222=>'Bluegum/Licorice', 260=>'Bluescreen',
        105=>'Blush', 106=>'Bonded', 381=>'Bone', 403=>'Bonfire', 320=>'Bottle Prism', 332=>'Bottle Prism/Black', 179=>'Brass',
        199=>'Brass/Black', 188=>'Brass/Brass', 107=>'Brick', 36=>'Bronze', 7=>'Brown', 28=>'Brown Logo', 270=>'Brown Multi',
        247=>'Brown Snake', 32=>'Brown Tartan', 244=>'Brownie', 1497=>'Brushblack', 290=>'Buff', 202=>'Burg.Ging/Espresso',
        145=>'Burgundy', 181=>'Burgundy Gingham', 187=>'Burgundy/Black', 334=>'Burgundy/Dusk', 198=>'Burgundy/Espresso',
        193=>'Burgundy/Gingham', 329=>'Burgundy/Red', 108=>'Camo', 153=>'Candy', 250=>'Carbon', 52=>'Cargo Green', 109=>'Caviar',
        1536=>'Chalk', 1490=>'Chambray', 110=>'Charcoal', 364=>'Charcoal Marle', 1552=>'Charred', 143=>'Cherry', 249=>'Chilli',
        1526=>'Chocolate', 1534=>'Clay Check', 1512=>'Cloud', 370=>'Coal', 1503=>'Coaldust', 254=>'Coffee', 478=>'Concrete',
        297=>'Coral', 1541=>'Covered', 8=>'Cream', 409=>'Cream/Black', 426=>'Crystal', 217=>'Crystal Black C',
        218=>'Crystal White C', 241=>'Crystal/Pcorn', 240=>'Crystal/Walnut', 1513=>'Dark', 471=>'Dark Animal', 111=>'Dark Blue',
        24=>'Dark Brown', 467=>'Dark Dusk', 267=>'Dark Dust', 1537=>'Dark Ink', 1501=>'Dark Mix', 31=>'Dark Tweed',
        405=>'DarkDust', 252=>'Darl Indigo', 165=>'Dash/Black', 432=>'Decoritif', 266=>'Delft', 41=>'Desert',
        324=>'Diamond Mix Print', 444=>'Dove', 1496=>'Drill', 291=>'Dusk', 322=>'Dusk/Storm', 469=>'Dust', 295=>'Ebony',
        1558=>'Eclipse', 97=>'Ecru', 476=>'Ecru/Black', 154=>'Electric', 374=>'Electric/Tarseal', 424=>'Emblem', 1557=>'Emerald',
        1500=>'Faux', 1498=>'Fine Black', 98=>'Flesh', 314=>'Flint', 142=>'Floral', 1533=>'Flower', 1516=>'Fluro Yellow',
        413=>'Fog', 215=>'Forest', 395=>'Frostbite', 160=>'Fuchsia/Pop', 243=>'Fudge', 1505=>'Garnet', 411=>'Gingham/Black',
        348=>'Glass/Silver', 126=>'Glow', 11=>'Gold', 399=>'Gothic', 410=>'Graphic/Yellow', 378=>'Graphite', 2=>'Green',
        327=>'Green Diamond', 83=>'Green Mix', 477=>'Green Stripe', 173=>'Green/Grey', 333=>'Green/Storm', 462=>'Green/White',
        1562=>'Greenacres', 6=>'Grey', 246=>'Grey Marle', 340=>'Grey/Black', 167=>'Grey/Blue', 341=>'Grey/Burgundy',
        475=>'Grey/Green', 48=>'Grey/Ink', 166=>'Grey/Navy', 169=>'Grey/Pink', 168=>'Grey/Purple', 447=>'HAHA', 450=>'HAHA X',
        155=>'Hands/Black', 239=>'Homemade Black', 176=>'Ice', 1550=>'Iceberg', 93=>'Indigo', 356=>'Indigo/Black', 39=>'Ink',
        1539=>'Ink Angel', 1538=>'Ink Mix', 415=>'Ink Tattoo', 70=>'Ink/Black', 353=>'Inkpen', 357=>'Inkpen/Black', 112=>'Iron',
        418=>'Ivory', 175=>'Jade', 427=>'Jet', 242=>'Jetblack', 368=>'Jetsam', 213=>'Kelp', 170=>'Khaki', 1509=>'Khol',
        414=>'Khol Tattoo', 227=>'Kidblack', 1504=>'Labyrinth', 1547=>'Lacquer', 287=>'Lapis', 226=>'Lateshow', 156=>'Lavender',
        360=>'Licorice', 354=>'Licorice/Black', 361=>'Licorice/Steel', 472=>'Light Animal', 1522=>'Light Blue', 20=>'Light Grey',
        114=>'Light Mix', 466=>'Light Orange', 90=>'Lime', 115=>'Logo', 400=>'Lotus', 1543=>'Lurex', 95=>'Mad Wax',
        257=>'Magenta', 59=>'Mahogany', 21=>'Maroon', 433=>'Marshmellow', 18=>'Matt Black', 19=>'Matt Grey', 376=>'Mauve',
        373=>'Mauve/Cream', 158=>'Melon', 437=>'Mesh', 96=>'Metal', 189=>'Midnight', 200=>'Midnight/Black', 253=>'Military',
        144=>'Milk', 417=>'Mist', 277=>'Monster', 15=>'Multi', 289=>'Mushroom', 269=>'Mustard', 92=>'N/A', 1546=>'Natural',
        398=>'Natural/Black', 17=>'Navy', 53=>'Navy Check', 1514=>'Navy Dots', 436=>'Navy Emblem', 1507=>'Navy Fleece',
        365=>'Navy Stripe', 174=>'Navy/Black', 337=>'Navy/Bleach', 372=>'Navy/Cream', 248=>'Navy/Ivory', 1518=>'Navy/Stripe',
        171=>'Navy/White', 336=>'Navy/Yellow', 1555=>'Negative', 366=>'Neo', 54=>'Nickel', 235=>'Noir', 231=>'Nori', 449=>'Nude',
        1524=>'Off White', 363=>'Oil', 91=>'Olive', 1540=>'Olive Angel', 1531=>'Olive Mix', 298=>'Onyx', 14=>'Orange',
        23=>'Orange Logo', 306=>'Orange Pony', 465=>'Orange/Pink', 1517=>'Orange/Print', 319=>'Orange/Pumice', 335=>'Orange/Red',
        345=>'Oxidised Silver', 294=>'Oyster', 161=>'Paint/Black', 401=>'Pale Blue', 1502=>'Pale Mix', 1528=>'Palm',
        164=>'Papaya', 272=>'Passport', 393=>'Pattern Black', 1521=>'Pattern/Black', 1519=>'Peach', 147=>'Pearl', 66=>'Peat',
        82=>'Peat/Black', 172=>'Petrol', 210=>'Petrol/Black', 197=>'Petrol/Charcoal', 47=>'Pewter', 1559=>'Phantom', 12=>'Pink',
        46=>'Pink Mix', 463=>'Pink/Yellow', 293=>'Pirate', 190=>'Pitch', 180=>'Pitch Tartan', 201=>'Pitch Tartan/Black',
        186=>'Pitch/Black', 208=>'Pitch/Tartan', 178=>'PJ Print', 338=>'Plaid', 27=>'Plum', 207=>'Plum/Black',
        191=>'Plum/Espresso', 209=>'Plum/Gingham', 1506=>'Polish', 162=>'Pop/White', 148=>'Poppy', 420=>'Porcelain',
        1556=>'Positive', 313=>'Potion', 122=>'Print', 22=>'Print Mix', 325=>'Prism Mix Print', 57=>'Pumice', 9=>'Purple',
        445=>'Putty', 454=>'Putty Black', 288=>'Quartz', 1525=>'Rainy Morning', 310=>'Raven', 1=>'Red', 1485=>'Red Check',
        271=>'Red Multi', 328=>'Red Prism', 232=>'Red Rose', 452=>'Red Slate', 474=>'Red Stripe', 100=>'Red/Black',
        323=>'Red/Orange', 149=>'Red/White', 350=>'Resin/Petals', 404=>'Rose', 219=>'Rose Red', 316=>'Rosewood', 300=>'Royal',
        305=>'Royal Pony', 1495=>'Ruby', 377=>'Rust', 292=>'Safari', 1529=>'Sand', 421=>'Saphire', 296=>'Sapphire',
        220=>'Sateen', 312=>'Satellite', 163=>'Scarlet', 221=>'Scuba', 317=>'Shadow', 10=>'Silver', 425=>'Silver Eyelet',
        251=>'Silver Marle', 16=>'Silver/ Gold', 388=>'Silver/Emerald', 276=>'Skeleton', 1535=>'Skin', 304=>'Sky Pony',
        443=>'Slate', 256=>'Smoke', 258=>'Smoke/Black', 255=>'Smoke/Green', 1553=>'Smudge', 116=>'Soap', 380=>'Spec',
        1494=>'Spotlight', 391=>'Stars', 42=>'Steel', 71=>'Steel/Black', 44=>'Steel/Sil', 347=>'Sterling Silver', 358=>'Stone',
        359=>'Stone/Black', 326=>'Storm', 299=>'String', 339=>'Stripe', 315=>'Stripe/Black', 389=>'Stripe/Dark Grey',
        390=>'Stripe/Khaki', 379=>'Syrah', 58=>'T-Shell', 1542=>'Tapestry', 302=>'Tar', 120=>'Tartan', 416=>'Taupe',
        344=>'Tear', 439=>'Thin Stripe', 262=>'Thunder', 1511=>'Thunderbird', 263=>'Tidal', 94=>'Tortoise',
        438=>'Triple Stripe', 123=>'Truffle', 1523=>'Turquoise', 117=>'Tweed', 396=>'Uzi', 311=>'Vamp', 367=>'Vanilla',
        268=>'Vintage Black', 369=>'Violet', 1548=>'Volcanic', 1482=>'Wallpaper', 4=>'White', 1554=>'White Dove',
        435=>'White Emblem', 422=>'White Eyelet', 440=>'White Veil', 35=>'White/Black', 118=>'White/Blue', 119=>'White/Green',
        286=>'White/Multi', 265=>'White/Navy', 101=>'White/Red', 430=>'Whitelawn', 237=>'Whitewash', 451=>'X', 13=>'Yellow',
        1508=>'Yellow Fleece', 464=>'Yellow/Orange', 238=>'Zambesi Black', 1549=>'Zinc');

    /**
     * Initialize the gateway and perform any setup actions required.
     * @param string $entityType
     * @return bool $success
     * @throws GatewayException
     */
    protected function _init($entityType)
    {
        $success = parent::_init($entityType);

        if ($entityType != 'product') {
            throw new GatewayException('Invalid entity type for this gateway');
            $success = FALSE;
        }else{
            $this->getServiceLocator()->get('logService')
                ->log(LogService::LEVEL_DEBUG, 'rex_p_init', 'Initialised Retailex product gateway.', array());
        }

        return $success;
    }

    /**
     * @param $colourId
     * @return string|NULL $colourString
     */
    public static function getColourString($colourId)
    {
        return self::getMappedString('colour', $colourId);
    }

    /**
     * @param int $colourString
     * @return int|NULL $colourId
     */
    public static function getColourId($colourString)
    {
        return self::getMappedId('colour', $colourString);
    }

    /**
     * Retrieve and action all updated records(either from polling, pushed data, or other sources).
     * @throws MagelinkException
     * @throws NodeException
     * @throws SyncException
     * @throws GatewayException
     */
    public function retrieve()
    {
        /** @var \Entity\Service\EntityConfigService $entityConfigService */
        $entityConfigService = $this->getServiceLocator()->get('entityConfigService');

        $this->getNewRetrieveTimestamp();
        $lastRetrieve = $this->getLastRetrieveDate();

        $this->getServiceLocator()->get('logService')
            ->log(LogService::LEVEL_INFO,
                'rex_p_re_time',
                'Retrieving products updated since '.$lastRetrieve,
               array('type'=>'product', 'datetime'=>$lastRetrieve)
            );

$retrieveProductId = 140268;$timestamp = time() - 1209600;$lastRetrieve = date('Y-m-d', $timestamp).'T'.date('H:i:s', $timestamp).'Z';$lastRetrieve=date('c', $timestamp);
        if ($this->soap) {
            $api = $this->soap->getApiType();
            $filter = array(
                'LastUpdated'=>$lastRetrieve,
                'ChannelId'=>$this->_node->getConfig('retailex-channel')
            );

            try{
                $call = 'ProductsGetBulkDetailsByChannel';
$call = 'ProductGetDetailsStockPricingByChannel';$filter = array('ProductId'=>$retrieveProductId, 'ChannelId'=>intval($this->_node->getConfig('retailex-channel')));
                $soapXml = $this->soap->call($call, $filter);

                $productsData = array();
var_dump($soapXml);
                if ($soapXml) {
                    $products = current($soapXml->xpath('//Products'));
                    foreach ($products->children() as $product) {
                        $productId = (string) $product->ProductId;
                        $productsData[$productId] = array();
                        foreach ($product as $key=>$value) {
                            $productsData[$productId][$key] = (string) $value;
                        }
var_dump($productsData[$productId]);die();
                    }
                }
            }catch (\Exception $exception) {
                throw new GatewayException($exception->getMessage(), $exception->getCode(), $exception);
            }
die();
            foreach ($productsData as $productData) {
                $productId = $productData['product_id'];
                $parentId = NULL; // TODO: Calculate
                $sku = $productData['sku'];

                $this->getServiceLocator()->get('logService')->log(LogService::LEVEL_DEBUGEXTRA,
                    'rex_psoap_data', 'Loaded product data from Retailex via SOAP api.',
                    array('sku'=>$productData['sku'], 'data'=>$productData)
                );

                if (isset($this->_attributeSets[intval($productData['set']) ])) {
                    $productData['product_class'] = $this->_attributeSets[intval($productData['set']) ]['name'];
                    unset($productData['set']);
                }else{
                    $this->getServiceLocator()->get('logService')
                        ->log(LogService::LEVEL_WARN,
                            'rex_psoap_uset',
                            'Unknown attribute set ID '.$productData['set'],
                           array('set'=>$productData['set'], 'sku'=>$productData['sku'])
                        );
                }

                if (isset($productData[''])) {
                    unset($productData['']);
                }

                unset($productData['category_ids']); // TODO parse into categories
                unset($productData['website_ids']); // Not used
                unset($productData['product_id']);
                unset($productData['sku']);

                try {
                    $this->processUpdate($productId, $sku, $storeId, $parentId, $productData);
                }catch (\Exception $exception) {
                    // store as sync issue
                    throw new GatewayException($exception->getMessage(), $exception->getCode(), $exception);
                }
            }
        }else{
            throw new NodeException('No valid API available for sync');
            $api = '-';
        }

        $this->_nodeService
            ->setTimestamp($this->_nodeEntity->getNodeId(), 'product', 'retrieve', $this->getNewRetrieveTimestamp());

        $seconds = ceil(time() - $this->getNewRetrieveTimestamp());
        $message = 'Retrieved '.count($results).' products in '.$seconds.'s up to '
            .strftime('%H:%M:%S, %d/%m', $this->retrieveTimestamp).' via '.$api.' api.';
        $logData = array('type'=>'product', 'amount'=>count($results), 'period [s]'=>$seconds);
        if (count($results) > 0) {
            $logData['per entity [s]'] = round($seconds / count($results), 3);
        }
        $this->getServiceLocator()->get('logService')->log(LogService::LEVEL_INFO, 'rex_p_re_no', $message, $logData);
    }

    /**
     * @param int $productId
     * @param string $sku
     * @param int $storeId
     * @param int $parentId
     * @param array $data
     * @return \Entity\Entity|NULL
     */
    protected function processUpdate($productId, $sku, $storeId, $parentId, array $data)
    {
        /** @var boolean $needsUpdate Whether we need to perform an entity update here */
        $needsUpdate = TRUE;

        $existingEntity = $this->_entityService->loadEntityLocal($this->_node->getNodeId(), 'product', 0, $productId);
        if (!$existingEntity) {
            $existingEntity = $this->_entityService->loadEntity($this->_node->getNodeId(), 'product', 0, $sku);
            $noneOrWrongLocalId = $this->_entityService->getLocalId($this->_node->getNodeId(), $existingEntity);

            if (!$existingEntity) {
                $existingEntity = $this->_entityService
                    ->createEntity($this->_node->getNodeId(), 'product', 0, $sku, $data, $parentId);
                $this->_entityService->linkEntity($this->_node->getNodeId(), $existingEntity, $productId);
                $this->getServiceLocator()->get('logService')
                    ->log(LogService::LEVEL_INFO,
                        'rex_p_new',
                        'New product '.$sku,
                       array('sku'=>$sku),
                       array('node'=>$this->_node, 'entity'=>$existingEntity)
                    );
                try{
                    $stockEntity = $this->_entityService
                        ->createEntity($this->_node->getNodeId(), 'stockitem', 0, $sku, array(), $existingEntity);
                    $this->_entityService->linkEntity($this->_node->getNodeId(), $stockEntity, $productId);
                }catch (\Exception $exception) {
                    $this->getServiceLocator() ->get('logService')
                        ->log(LogService::LEVEL_WARN,
                            'rex_p_si_ex',
                            'Already existing stockitem for new product '.$sku,
                           array('sku'=>$sku),
                           array('node'=>$this->_node, 'entity'=>$existingEntity)
                        );
                }
                $needsUpdate = FALSE;
            }elseif ($noneOrWrongLocalId != NULL) {
                $this->_entityService->unlinkEntity($this->_node->getNodeId(), $existingEntity);
                $this->_entityService->linkEntity($this->_node->getNodeId(), $existingEntity, $productId);

                $stockEntity = $this->_entityService->loadEntity($this->_node->getNodeId(), 'stockitem', 0, $sku);
                if ($this->_entityService->getLocalId($this->_node->getNodeId(), $stockEntity) != NULL) {
                    $this->_entityService->unlinkEntity($this->_node->getNodeId(), $stockEntity);
                }
                $this->_entityService->linkEntity($this->_node->getNodeId(), $stockEntity, $productId);

                $this->getServiceLocator() ->get('logService')
                    ->log(LogService::LEVEL_ERROR,
                        'rex_p_relink',
                        'Incorrectly linked product '.$sku.' ('.$noneOrWrongLocalId.'). Re-linked now.',
                       array('code'=>$sku, 'wrong local id'=>$noneOrWrongLocalId),
                       array('node'=>$this->_node, 'entity'=>$existingEntity)
                    );
            }else{
                $this->getServiceLocator() ->get('logService')
                    ->log(LogService::LEVEL_INFO,
                        'rex_p_link',
                        'Unlinked product '.$sku,
                       array('sku'=>$sku),
                       array('node'=>$this->_node, 'entity'=>$existingEntity)
                    );
                $this->_entityService->linkEntity($this->_node->getNodeId(), $existingEntity, $productId);
            }
        }else{
            $this->getServiceLocator()->get('logService')
                ->log(LogService::LEVEL_INFO,
                    'rex_p_upd',
                    'Updated product '.$sku,
                   array('sku'=>$sku),
                   array('node'=>$this->_node, 'entity'=>$existingEntity, 'data'=>$data)
                );
        }

        if ($needsUpdate) {
            $this->_entityService->updateEntity($this->_node->getNodeId(), $existingEntity, $data, FALSE);
        }

        return $existingEntity;
    }

    /**
     * Load detailed product data from Retailex
     * @param $productId
     * @param $storeId
     * @param \Entity\Service\EntityConfigService $entityConfigService
     * @return array
     * @throws \Magelink\Exception\MagelinkException
     */
    public function loadFullProduct($productId, $storeId, \Entity\Service\EntityConfigService $entityConfigService) {

        $additional = $this->_node->getConfig('product_attributes');
        if (is_string($additional)) {
            $additional = explode(',', $additional);
        }
        if (!$additional || !is_array($additional)) {
            $additional = array();
        }

        $data = array(
            $productId,
            $storeId,
            array('additional_attributes'=>$additional),
            'id',
        );

        $productInfo = $this->soap->call('catalogProductInfo', $data);

        if (!$productInfo && !$productInfo['sku']) {
            // store as sync issue
            throw new GatewayException('Invalid product info response');
            $data = NULL;
        }else{
            $data = $this->convertFromRetailex($productInfo, $additional);

            foreach ($additional as $attributeCode) {
                $attributeCode = strtolower(trim($attributeCode));

                if (strlen($attributeCode)) {
                    if (!array_key_exists($attributeCode, $data)) {
                        $data[$attributeCode] = NULL;
                    }

                    if (!$entityConfigService->checkAttribute('product', $attributeCode)) {
                        $entityConfigService->createAttribute(
                            $attributeCode,
                            $attributeCode,
                            0,
                            'varchar',
                            'product',
                            'Custom Retailex attribute'
                        );

                        try {
                            $this->getServiceLocator()->get('nodeService')->subscribeAttribute(
                                $this->_node->getNodeId(),
                                $attributeCode,
                                'product'
                            );
                        }catch (\Exception $exception) {
                            // Store as sync issue
                            throw new GatewayException($exception->getMessage(), $exception->getCode(), $exception);
                            $data = NULL;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Converts Retailex-named attributes into our internal Magelink attributes / formats.
     * @param array $rawData Input array of Retailex attribute codes
     * @param array $additional Additional product attributes to load in
     * @return array
     */
    protected function convertFromRetailex($rawData, $additional) {
        $data = array();
        if (isset($rawData['type_id'])) {
            $data['type'] = $rawData['type_id'];
        }else{
            if (isset($rawData['type'])) {
                $data['type'] = $rawData['type'];
            }else{
                $data['type'] = NULL;
            }
        }
        if (isset($rawData['name'])) {
            $data['name'] = $rawData['name'];
        }else{
            $data['name'] = NULL;
        }
        if (isset($rawData['description'])) {
            $data['description'] = $rawData['description'];
        }else{
            $data['description'] = NULL;
        }
        if (isset($rawData['short_description'])) {
            $data['short_description'] = $rawData['short_description'];
        }else{
            $data['short_description'] = NULL;
        }
        if (isset($rawData['status'])) {
            $data['enabled'] =($rawData['status'] == 1) ? 1 : 0;
        }else{
            $data['enabled'] = 0;
        }
        if (isset($rawData['visibility'])) {
            $data['visible'] =($rawData['visibility'] == 4) ? 1 : 0;
        }else{
            $data['visible'] = 0;
        }
        if (isset($rawData['price'])) {
            $data['price'] = $rawData['price'];
        }else{
            $data['price'] = NULL;
        }
        if (isset($rawData['tax_class_id'])) {
            $data['taxable'] =($rawData['tax_class_id'] == 2) ? 1 : 0;
        }else{
            $data['taxable'] = 0;
        }
        if (isset($rawData['special_price'])) {
            $data['special_price'] = $rawData['special_price'];

            if (isset($rawData['special_from_date'])) {
                $data['special_from_date'] = $rawData['special_from_date'];
            }else{
                $data['special_from_date'] = NULL;
            }
            if (isset($rawData['special_to_date'])) {
                $data['special_to_date'] = $rawData['special_to_date'];
            }else{
                $data['special_to_date'] = NULL;
            }
        }else{
            $data['special_price'] = NULL;
            $data['special_from_date'] = NULL;
            $data['special_to_date'] = NULL;
        }

        if (isset($rawData['additional_attributes'])) {
            foreach ($rawData['additional_attributes'] as $pair) {
                $attributeCode = trim(strtolower($pair['key']));
                if (!in_array($attributeCode, $additional)) {
                    throw new GatewayException('Invalid attribute returned by Retailex: '.$attributeCode);
                }
                if (isset($pair['value'])) {
                    $data[$attributeCode] = $pair['value'];
                }else{
                    $data[$attributeCode] = NULL;
                }
            }
        }else{
            foreach ($additional as $code) {
                if (isset($rawData[$code])) {
                    $data[$code] = $rawData[$code];
                }
            }
        }

        return $data;
    }

    /**
     * Restructure data for soap call and return this array.
     * @param array $data
     * @param array $customAttributes
     * @return array $soapData
     * @throws \Magelink\Exception\MagelinkException
     */
    protected function getUpdateDataForSoapCall(array $data, array $customAttributes)
    {
        // Restructure data for soap call
        $soapData = array(
            'additional_attributes'=>array(
                'single_data'=>array(),
                'multi_data'=>array()
            )
        );
        $removeSingleData = $removeMultiData = TRUE;

        foreach ($data as $code=>$value) {
            $isCustomAttribute = in_array($code, $customAttributes);
            if ($isCustomAttribute) {
                if (is_array($data[$code])) {
                    // TODO(maybe) : Implement
                    throw new GatewayException("This gateway doesn't support multi_data custom attributes yet.");
                    $removeMultiData = FALSE;
                }else{
                    $soapData['additional_attributes']['single_data'][] = array(
                        'key'=>$code,
                        'value'=>$value,
                    );
                    $removeSingleData = FALSE;
                }
            }else{
                $soapData[$code] = $value;
            }
        }

        if ($removeSingleData) {
            unset($data['additional_attributes']['single_data']);
        }
        if ($removeMultiData) {
            unset($data['additional_attributes']['multi_data']);
        }
        if ($removeSingleData && $removeMultiData) {
            unset($data['additional_attributes']);
        }

        return $soapData;
    }

    /**
     * Write out all the updates to the given entity.
     * @param \Entity\Entity $entity
     * @param string[] $attributes
     * @param int $type
     */
    public function writeUpdates(\Entity\Entity $entity, $attributes, $type = Update::TYPE_UPDATE)
    {
        $nodeId = $this->_node->getNodeId();
        $sku = $entity->getUniqueId();

        $customAttributes = $this->_node->getConfig('product_attributes');
        if (is_string($customAttributes)) {
            $customAttributes = explode(',', $customAttributes);
        }
        if (!$customAttributes || !is_array($customAttributes)) {
            $customAttributes = array();
        }

        $this->getServiceLocator()->get('logService')
            ->log(LogService::LEVEL_DEBUGEXTRA,
                'rex_p_wrupd',
                'Attributes for update of product '.$sku.': '.var_export($attributes, TRUE),
               array('attributes'=>$attributes, 'custom'=>$customAttributes),
               array('entity'=>$entity)
            );

        $originalData = $entity->getFullArrayCopy();
        $attributeCodes = array_unique(array_merge(
            //array('special_price', 'special_from_date', 'special_to_date'), // force update of these attributes
            //$customAttributes,
            $attributes
        ));

        foreach ($originalData as $attributeCode=>$attributeValue) {
            if (!in_array($attributeCode, $attributeCodes)) {
                unset($originalData[$attributeCode]);
            }
        }

        $data = array();
        if (count($originalData) == 0) {
            $this->getServiceLocator()->get('logService')
                ->log(LogService::LEVEL_INFO,
                    'rex_p_wrupd_non',
                    'No update required for '.$sku.' but requested was '.implode(', ', $attributes),
                    array('attributes'=>$attributes),
                    array('entity'=>$entity)
                );
        }else{
            /** @var RetailexService $magentoService */
            $magentoService = $this->getServiceLocator()->get('magentoService');

            foreach ($originalData as $code=>$value) {
                $mappedCode = $magentoService->getMappedCode('product', $code);
                switch ($mappedCode) {
                    case 'price':
                    case 'special_price':
                    case 'special_from_date':
                    case 'special_to_date':
                        $value = ($value ? $value : NULL);
                    case 'name':
                    case 'description':
                    case 'short_description':
                    case 'weight':
                    case 'barcode':
                    case 'bin_location':
                    case 'msrp':
                    case 'cost':
                        // Same name in both systems
                        $data[$code] = $value;
                        break;
                    case 'enabled':
                        $data['status'] = ($value == 1 ? 1 : 2);
                        break;
                    case 'taxable':
                        $data['tax_class_id'] = ($value == 1 ? 2 : 1);
                        break;
                    case 'visible':
                        $data['visibility'] = ($value == 1 ? 4 : 1);
                        break;
                    // ToDo (maybe) : Add logic for this custom attributes
                    case 'brand':
                    case 'size':
                        // Ignore attributes
                        break;
                    case 'product_class':
                    case 'type':
                        if ($type != Update::TYPE_CREATE) {
                            // ToDo: Log error(but no exception)
                        }else{
                            // Ignore attributes
                        }
                        break;
                    default:
                        $this->getServiceLocator()->get('logService')
                            ->log(LogService::LEVEL_WARN,
                                'rex_p_wr_invdata',
                                'Unsupported attribute for update of '.$sku.': '.$attributeCode,
                               array('attribute'=>$attributeCode),
                               array('entity'=>$entity)
                            );
                        // Warn unsupported attribute
                }
            }

            $localId = $this->_entityService->getLocalId($this->_node->getNodeId(), $entity);

            $storeDataByStoreId = $this->_node->getStoreViews();
            if (count($storeDataByStoreId) > 0 && $type != Update::TYPE_DELETE) {
                $dataPerStore[0] = $data;
                foreach (array('price', 'special_price', 'msrp', 'cost') as $code) {
                    if (array_key_exists($code, $data)) {
                        unset($data[$code]);
                    }
                }

                $websiteIds = array();
                foreach ($storeDataByStoreId as $storeId=>$storeData) {
                    $dataToMap = $magentoService->mapProductData($data, $storeId, FALSE, TRUE);
                    if ($magentoService->isStoreUsingDefaults($storeId)) {
                        $dataToCheck = $dataPerStore[0];
                    }else{
                        $dataToCheck = $dataToMap;
                    }

                    $isEnabled = isset($dataToCheck['price']);
                    if ($isEnabled) {
                        $websiteIds[] = $storeData['website_id'];
                        $logCode = 'rex_p_wrupd_wen';
                        $logMessage = 'enabled';
                    }else{
                        $logCode = 'rex_p_wrupd_wdis';
                        $logMessage = 'disabled';
                    }

                    $logMessage = 'Product '.$sku.' will be '.$logMessage.' on website '.$storeData['website_id'].'.';
                    $logData = array('store id'=>$storeId, 'dataToMap'=>$dataToMap, 'dataToCheck'=>$dataToCheck);

                    $this->getServiceLocator()->get('logService')
                        ->log(LogService::LEVEL_DEBUGINTERNAL, $logCode, $logMessage, $logData);

                    $dataPerStore[$storeId] = $dataToMap;
                }
                unset($data, $dataToMap, $dataToCheck);

                $storeIds = array_merge(array(0), array_keys($storeDataByStoreId));
                $this->getServiceLocator()->get('logService')->log(LogService::LEVEL_DEBUGINTERNAL,
                    'rex_p_wrupd_stor',
                    'StoreIds '.json_encode($storeIds).' (type: '.$type.'), websiteIds '.json_encode($websiteIds).'.',
                    array('store data'=>$storeDataByStoreId)
                );

                foreach ($storeIds as $storeId) {
                    $productData = $dataPerStore[$storeId];
                    $productData['website_ids'] = $websiteIds;

                    if ($magentoService->isStoreUsingDefaults($storeId)) {
                        $setSpecialPrice = FALSE;
                        unset($productData['special_price']);
                        unset($productData['special_from_date']);
                        unset($productData['special_to_date']);
                    }elseif (isset($productData['special_price'])) {
                        $setSpecialPrice = FALSE;
                    }elseif ($storeId === 0) {
                        $setSpecialPrice = FALSE;
                        $productData['special_price'] = NULL;
                        $productData['special_from_date'] = NULL;
                        $productData['special_to_date'] = NULL;
                    }else{
                        $setSpecialPrice = FALSE;
                        $productData['special_price'] = '';
                        $productData['special_from_date'] = '';
                        $productData['special_to_date'] = '';
                    }

                    $soapData = $this->getUpdateDataForSoapCall($productData, $customAttributes);
                    $logData = array(
                        'type'=>$entity->getData('type'),
                        'store id'=>$storeId,
                        'product data'=>$productData,
                    );
                    $soapResult = NULL;

                    $updateViaDbApi = ($this->db && $localId && $storeId == 0);
                    if ($updateViaDbApi) {
                        $api = 'DB';
                    }else{
                        $api = 'SOAP';
                    }

                    if ($type == Update::TYPE_UPDATE || $localId) {
                        if ($updateViaDbApi) {
                            try{
                                $tablePrefix = 'catalog_product';
                                $rowsAffected = $this->db->updateEntityEav(
                                    $tablePrefix,
                                    $localId,
                                    $entity->getStoreId(),
                                    $productData
                                );

                                if ($rowsAffected != 1) {
                                    throw new MagelinkException($rowsAffected.' rows affected.');
                                }
                            }catch(\Exception $exception) {
                                $this->_entityService->unlinkEntity($nodeId, $entity);
                                $localId = NULL;
                                $updateViaDbApi = FALSE;
                            }
                        }

                        $logMessage = 'Updated product '.$sku.' on store '.$storeId.' ';
                        if ($updateViaDbApi) {
                            $logLevel = LogService::LEVEL_INFO;
                            $logCode = 'rex_p_wrupddb';
                            $logMessage .= 'successfully via DB api with '.implode(', ', array_keys($productData));
                        }else{
                            try{
                                $request = array($sku, $soapData, $storeId, 'sku');
                                $soapResult = array('update'=>
                                    $this->soap->call('catalogProductUpdate', $request));
                                if ($setSpecialPrice) {
                                    $requestSpecial = array(
                                        $sku,
                                        $productData['special_price'],
                                        $productData['special_from_date'],
                                        $productData['special_to_date'],
                                        $storeId,
                                        'sku'
                                    );
                                    $soapResult['special'] =
                                        $this->soap->call('catalogProductSetSpecialPrice', $requestSpecial);
                                }
                            }catch(\Exception $exception) {
                                $soapResult = FALSE;
                                $soapFaultMessage = $exception->getPrevious()->getMessage();
                                if (strpos($soapFaultMessage, 'Product not exists') !== FALSE) {
                                    $type = Update::TYPE_CREATE;
                                }
                            }

                            $logLevel = ($soapResult ? LogService::LEVEL_INFO : LogService::LEVEL_ERROR);
                            $logCode = 'rex_p_wrupdsoap';
                            if ($api != 'SOAP') {
                                $logMessage = $api.' update failed. Removed local id '.$localId
                                    .' from node '.$nodeId.'. '.$logMessage;
                                if (isset($exception)) {
                                    $logData[strtolower($api.' error')] = $exception->getMessage();
                                }
                            }

                            $logMessage .= ($soapResult ? 'successfully' : 'without success').' via SOAP api.'
                                .($type == Update::TYPE_CREATE ? ' Try to create now.' : '');
                            $logData['soap data'] = $soapData;
                            $logData['soap result'] = $soapResult;
                        }
                        $this->getServiceLocator()->get('logService')->log($logLevel, $logCode, $logMessage, $logData);
                    }

                    if ($type == Update::TYPE_CREATE) {

                        $attributeSet = NULL;
                        foreach ($this->_attributeSets as $setId=>$set) {
                            if ($set['name'] == $entity->getData('product_class', 'default')) {
                                $attributeSet = $setId;
                                break;
                            }
                        }
                        if ($attributeSet === NULL) {
                            $message = 'Invalid product class '.$entity->getData('product_class', 'default');
                            throw new \Magelink\Exception\SyncException($message);
                        }

                        $message = 'Creating product(SOAP) : '.$sku.' with '.implode(', ', array_keys($productData));
                        $logData['set'] = $attributeSet;
                        $this->getServiceLocator()->get('logService')
                            ->log(LogService::LEVEL_INFO, 'rex_p_wr_cr', $message, $logData);

                        $request = array(
                            $entity->getData('type'),
                            $attributeSet,
                            $sku,
                            $soapData,
                            $entity->getStoreId()
                        );

                        try{
                            $soapResult = $this->soap->call('catalogProductCreate', $request);
                            $soapFault = NULL;
                        }catch(\Exception $exception) {
                            $soapResult = FALSE;
                            $soapFault = $exception->getPrevious();
                            $soapFaultMessage = $soapFault->getMessage();
                            if ($soapFaultMessage == 'The value of attribute "SKU" must be unique') {
                                $this->getServiceLocator()->get('logService')
                                    ->log(LogService::LEVEL_WARN,
                                        'rex_p_wr_duperr',
                                        'Creating product '.$sku.' hit SKU duplicate fault',
                                        array(),
                                        array('entity'=>$entity, 'soap fault'=>$soapFault)
                                    );

                                $check = $this->soap->call('catalogProductInfo', array($sku, 0, array(), 'sku'));
                                if (!$check || !count($check)) {
                                    throw new MagelinkException(
                                        'Retailex complained duplicate SKU but we cannot find a duplicate!'
                                    );

                                }else{
                                    $found = FALSE;
                                    foreach ($check as $row) {
                                        if ($row['sku'] == $sku) {
                                            $found = TRUE;

                                            $this->_entityService->linkEntity($nodeId, $entity, $row['product_id']);
                                            $this->getServiceLocator()->get('logService')
                                                ->log(LogService::LEVEL_INFO,
                                                    'rex_p_wr_dupres',
                                                    'Creating product '.$sku.' resolved SKU duplicate fault',
                                                    array('local_id'=>$row['product_id']),
                                                    array('entity'=>$entity)
                                                );
                                        }
                                    }

                                    if (!$found) {
                                        $message = 'Retailex found duplicate SKU '.$sku
                                            .' but we could not replicate. Database fault?';
                                        throw new MagelinkException($message);
                                    }
                                }
                            }
                        }

                        if ($soapResult) {
                            $this->_entityService->linkEntity($nodeId, $entity, $soapResult);
                            $type = Update::TYPE_UPDATE;

                            $logData['soap data'] = $soapData;
                            $this->getServiceLocator()->get('logService')->log(LogService::LEVEL_INFO,
                                'rex_p_wr_loc_id',
                                'Added product local id '.$soapResult.' for '.$sku.' ('.$nodeId.')',
                                $logData
                            );
                        }else{
                            $message = 'Error creating product '.$sku.' in Retailex!';
                            throw new MagelinkException($message, 0, $soapFault);
                        }
                    }
                }
                unset($dataPerStore);
            }
        }
    }

    /**
     * Write out the given action.
     * @param Action $action
     * @throws MagelinkException
     */
    public function writeAction(Action $action)
    {
        $entity = $action->getEntity();
        switch($action->getType()) {
            case 'delete':
                $this->soap->call('catalogProductDelete',array($entity->getUniqueId(), 'sku'));
                $success = TRUE;
                break;
            default:
                throw new MagelinkException('Unsupported action type '.$action->getType().' for Retailex Orders.');
                $success = FALSE;
        }

        return $success;
    }

}
