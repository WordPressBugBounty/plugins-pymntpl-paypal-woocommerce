<?php

namespace PaymentPlugins\WooCommerce\PPCP\Utilities;

use PaymentPlugins\PayPalSDK\V1\Tracker;

class ShippingUtil {

	public static function get_carriers() {
		return apply_filters( 'wc_ppcp_get_shipping_carriers', [
			'global' => [
				'name'  => 'Global',
				'items' => [
					'A2B_BA'                     => 'A2B Express Logistics',
					'ACILOGISTIX'                => 'ACI Logistix',
					'ACOMMERCE'                  => 'ACOMMERCE',
					'ADS'                        => 'ADS Express',
					'AEROFLASH'                  => 'AEROFLASH',
					'AIR_21'                     => 'AIR 21',
					'AIRSPEED'                   => 'AIRSPEED',
					'AIRTERRA'                   => 'Airterra',
					'ALLEGRO'                    => 'Allegro',
					'ALLJOY'                     => 'ALLJOY SUPPLY CHAIN',
					'AMAZON_EMAIL_PUSH'          => 'Amazon',
					'AMAZON_UK_API'              => 'Amazon UK API',
					'AMS_GRP'                    => 'AMS Group',
					'ANTERAJA'                   => 'Anteraja',
					'ARAMEX'                     => 'Aramex',
					'ASENDIA_DE'                 => 'Asendia DE',
					'ASENDIA'                    => 'Asendia Global',
					'ASSOCIATED_COURIERS'        => 'Associated Couriers',
					'ATA'                        => 'ATA',
					'ATSHEALTHCARE'              => 'ATS Healthcare',
					'AUEXPRESS'                  => 'Au Express',
					'AVERITT'                    => 'Averitt Express',
					'AXLEHIRE'                   => 'AxleHire',
					'BDMNET'                     => 'BDMnet',
					'BEL_BELGIUM_POST'           => 'Bel Belgium Post',
					'BLR_BELPOST'                => 'Belpost',
					'BERT'                       => 'BERT',
					'BETTERTRUCKS'               => 'Better Trucks',
					'BIGSMART'                   => 'Big Smart',
					'BJSHOMEDELIVERY'            => 'BJS Distribution courier',
					'BLUEDART'                   => 'BLUEDART',
					'BOLLORE_LOGISTICS'          => 'Bollore Logistics',
					'BPOST_INT'                  => 'Bpost international',
					'BUFFALO'                    => 'BUFFALO',
					'BURD'                       => 'Burd Delivery',
					'CAGO'                       => 'Cago',
					'CANPAR'                     => 'CANPAR',
					'CAPITAL'                    => 'Capital Transport',
					'CARRY_FLAP'                 => 'Carry-Flap Co.',
					'CDLDELIVERS'                => 'CDL Last Mile',
					'CEVA'                       => 'CEVA LOGISTICS',
					'CIRROTRACK'                 => 'CIRRO Track',
					'CJ_GLS'                     => 'CJ GLS',
					'CJ_LOGISTICS'               => 'CJ Logistics International',
					'CJ_PHILIPPINES'             => 'CJ Philippines',
					'CN_LOGISTICS'               => 'CN Logistics',
					'COLLECTPLUS'                => 'COLLECTPLUS',
					'COM1EXPRESS'                => 'ComOne Express',
					'COPA_COURIER'               => 'Copa Airlines Courier',
					'CORETRAILS'                 => 'Coretrails',
					'CORREOS_ES'                 => 'Correos Express (www.correos.es)',
					'FRANCO'                     => 'Corriere Franco',
					'COURIER_POST'               => 'COURIER POST',
					'COURIERPLUS'                => 'COURIERPLUS',
					'CRLEXPRESS'                 => 'CRL Express',
					'CROSSFLIGHT'                => 'Crossflight Limited',
					'DACHSER'                    => 'DACHSER',
					'DAESHIN'                    => 'Daeshin',
					'DAIICHI'                    => 'Daiichi Freight System Inc',
					'DANNIAO'                    => 'Danniao',
					'DAYROSS'                    => 'Day & Ross',
					'DYLT'                       => 'Daylight Transport',
					'DDEXPRESS'                  => 'DD Express Courier',
					'DE_DHL'                     => 'DE DHL',
					'DELIVERYOURPARCEL_ZA'       => 'Deliver Your Parcel',
					'DELIVER_IT'                 => 'Deliver-iT',
					'DELIVERE'                   => 'Delivere',
					'DELTEC_DE'                  => 'DELTEC DE',
					'GODEPENDABLE'               => 'Dependable Supply Chain Services',
					'DEUTSCHE_DE'                => 'Deutsche DE',
					'DHL_ACTIVE_TRACING'         => 'DHL Active Tracing',
					'DHL_ECOMMERCE_GC'           => 'DHL eCommerce Greater China',
					'DHL_FREIGHT'                => 'DHL Freight',
					'DHL'                        => 'Dhl Global',
					'DHL_GLOBAL_MAIL'            => 'Dhl Global Mail',
					'IT_DHL_ECOMMERCE'           => 'DHL International',
					'DHL_PARCEL_NL'              => 'DHL Parcel NL',
					'DHL_PIECEID'                => 'Dhl Pieceid',
					'DHL_SUPPLY_CHAIN_AU'        => 'Dhl Supply Chain AU',
					'DHL_SUPPLYCHAIN_ID'         => 'DHL Supply Chain Indonesia',
					'DHLPARCEL_UK'               => 'Dhlparcel UK',
					'DIALOGO_LOGISTICA'          => 'Dialogo Logistica',
					'DIREX'                      => 'Direx',
					'DISCOUNTPOST'               => 'Discount Post',
					'DMFGROUP'                   => 'DMF',
					'DPD'                        => 'DPD',
					'DPD_DE'                     => 'Dpd DE',
					'DPD_DELISTRACK'             => 'DPD delistrack',
					'DPD_NL'                     => 'DPD Netherlands',
					'DPD_UK'                     => 'Dpd UK',
					'CN_DPEX'                    => 'DPEX',
					'DPEX'                       => 'DPEX (www.dpex.com)',
					'DSV'                        => 'DSV courier',
					'DX'                         => 'DX',
					'DX_FREIGHT'                 => 'DX Freight',
					'EASYROUTES'                 => 'EasyRoutes',
					'ECARGO'                     => 'ECARGO',
					'ECEXPRESS'                  => 'ECexpress',
					'ECMS'                       => 'ECMS International Logistics Co.',
					'ECOFREIGHT'                 => 'Eco Freight',
					'ECOUTIER'                   => 'Ecoutier',
					'ECPARCEL'                   => 'Ecparcel',
					'EFS'                        => 'EFS (E-commerce Fulfillment Service)',
					'ELTA_GR'                    => 'Elta GR',
					'ARE_EMIRATES_POST'          => 'Emirates Post',
					'EMS'                        => 'EMS',
					'EMS_CN'                     => 'Ems CN',
					'ENERGOLOGISTIC'             => 'Energo Logistic',
					'ETOMARS'                    => 'Etomars',
					'ETONAS'                     => 'Etonas',
					'ETOTAL'                     => 'Etotal Solution Limited',
					'ETOWER'                     => 'Etower',
					'EURODIS'                    => 'Eurodis',
					'EWE'                        => 'EWE Global Express',
					'EXPEDITORS'                 => 'Expeditors',
					'FNF_ZA'                     => 'Fast & Furious',
					'FASTDESPATCH'               => 'Fast Despatch Logistics Limited',
					'FASTBOX'                    => 'Fastbox',
					'FASTSHIP'                   => 'Fastship Express',
					'FASTTRACK'                  => 'Fasttrack',
					'FASTWAY_AU'                 => 'Fastway AU',
					'FASTWAY_ZA'                 => 'Fastway ZA',
					'FEDEX_FR'                   => 'FedEx® Freight',
					'FERCAM_IT'                  => 'Fercam IT',
					'FINMILE'                    => 'Fin Mile',
					'FLIGHTLG'                   => 'Flight Logistics Group',
					'FLIPXP'                     => 'FlipXpress',
					'FLYTEXPRESS'                => 'FLYTEXPRESS',
					'FORWARDAIR'                 => 'Forward Air',
					'FOUR_PX_EXPRESS'            => 'FOUR PX EXPRESS',
					'FOURKITES'                  => 'Fourkites',
					'FR_COLISSIMO'               => 'FR Colissimo',
					'FR_MONDIAL'                 => 'FR Mondial',
					'FRONTDOORCORP'              => 'FRONTdoor Collective',
					'FUJEXP'                     => 'FUJIE EXPRESS',
					'GDPHARM'                    => 'GDPharm Logistics',
					'GEODIS'                     => 'GEODIS',
					'GPOST'                      => 'Georgian Post',
					'GIAO_HANG'                  => 'Giao hàng nhanh',
					'GIO_ECOURIER'               => 'GIO Express Inc',
					'GOGLOBALPOST'               => 'Global Post',
					'GLOBEGISTICS'               => 'GLOBEGISTICS',
					'GLS'                        => 'GLS',
					'GLS_SPAIN'                  => 'Gls Spain',
					'GOLS'                       => 'GO Logistics & Storage',
					'GOPEOPLE'                   => 'Go People',
					'GORUSH'                     => 'Go Rush',
					'GOBOLT'                     => 'GoBolt',
					'GOFO_EXPRESS'               => 'Gofo Express',
					'GPS'                        => 'GPS',
					'GREYHOUND'                  => 'GREYHOUND',
					'HANJIN'                     => 'HanJin',
					'HELLENIC_POST'              => 'Hellenic (Greece) Post',
					'HELLMANN'                   => 'Hellmann Worldwide Logistics',
					'HEPSIJET'                   => 'Hepsijet',
					'HERMESWORLD_UK'             => 'Hermesworld UK',
					'HEROEXPRESS'                => 'Hero Express',
					'HFD'                        => 'HFD',
					'HK_RPX'                     => 'HK Rpx',
					'HSDEXPRESS'                 => 'HSDEXPRESS',
					'HUANTONG'                   => 'HuanTong',
					'HUBBED'                     => 'HUBBED',
					'POSTUR_IS'                  => 'Iceland Post',
					'ICSCOURIER'                 => 'ICS COURIER',
					'IDEXPRESS_ID'               => 'Idexpress Indonesia',
					'IDN_POS'                    => 'Idn Pos',
					'IML'                        => 'IML courier',
					'IMX'                        => 'IMX',
					'INDIA_POST'                 => 'India Post Domestic',
					'INDIA_POST_INT'             => 'India Post International',
					'INPOST_UK'                  => 'InPost',
					'INTERSMARTTRANS'            => 'INTERSMARTTRANS & SOLUTIONS SL',
					'INTEX_DE'                   => 'INTEX Paketdienst GmbH',
					'ITHINKLOGISTICS'            => 'Ithink Logistics',
					'JTCARGO'                    => 'J&T CARGO',
					'JTEXPRESS_PH'               => 'J&T Express Philippines',
					'JADLOG'                     => 'Jadlog',
					'CN_JCEX'                    => 'JCEX courier',
					'JD_WORLDWIDE'               => 'JD Worldwide',
					'JERSEYPOST_ATLAS'           => 'Jersey Post Group',
					'JETSHIP_MY'                 => 'Jetship My',
					'IDN_JNE'                    => 'JNE Express (Jalur Nugraha Ekakurir)',
					'JOYINGBOX'                  => 'Joyingbox',
					'JUSDASR'                    => 'JUSDASR',
					'KARGOMKOLAY'                => 'KargomKolay (CargoMini)',
					'KEDAEX'                     => 'KedaEX',
					'HK_TGX'                     => 'Kerry Express Hong Kong',
					'THA_KERRY'                  => 'Kerry Express Thailand',
					'KNG'                        => 'Keuhne + Nagel Global',
					'KOLAY_GELSIN'               => 'Kolay Gelsin',
					'KOMON_EXPRESS'              => 'Komon Express',
					'KRONOS'                     => 'Kronos Express',
					'KUEHNE'                     => 'Kuehne + Nagel',
					'LIEFERGRUN'                 => 'LIEFERGRUN',
					'LTU_LIETUVOS'               => 'Lietuvos pastas',
					'LINKBRIDGE'                 => 'Link Bridge(BeiJing)international logistics co.',
					'LION_PARCEL'                => 'LION PARCEL',
					'LOGISTICSWORLDWIDE_KR'      => 'LOGISTICSWORLDWIDE KR',
					'LOGISTICSWORLDWIDE_MY'      => 'LOGISTICSWORLDWIDE MY',
					'LOGOIX'                     => 'LogoiX',
					'LOGWIN_LOGISTICS'           => 'Logwin Logistics',
					'LUWJISTIK'                  => 'Luwjistik',
					'MX_CARGO'                   => 'M&X cargo',
					'M3LOGISTICS'                => 'M3 Logistics',
					'MADROOEX'                   => 'Madrooex',
					'MYS_EMS'                    => 'Malaysia Post EMS / Pos Laju',
					'MALCA_AMIT'                 => 'Malca-Amit',
					'MEDAFRICA'                  => 'Med Africa Logistics',
					'MEEST'                      => 'Meest',
					'MHI'                        => 'Mhi',
					'MIKROPAKKET'                => 'Mikropakket',
					'MISUMI_CN'                  => 'MISUMI Group Inc.',
					'MNG_KARGO'                  => 'MNG Kargo',
					'MOBI_BR'                    => 'Mobi Logistica',
					'MOOVIN'                     => 'Moovin',
					'MORNINGLOBAL'               => 'Morning Global',
					'MOVIANTO'                   => 'Movianto',
					'MUDITA'                     => 'MUDITA',
					'MYDYNALOGIC'                => 'My DynaLogic',
					'NMTRANSFER'                 => 'N&M Transfer Co., Inc.',
					'NETLOGIXGROUP'              => 'Netlogix',
					'NEWZEALAND_COURIERS'        => 'NEW ZEALAND COURIERS',
					'NEWGISTICS'                 => 'Newgistics',
					'NEWGISTICSAPI'              => 'Newgistics API',
					'NIGHTLINE_UK'               => 'Nightline UK',
					'NIMBUSPOST'                 => 'NimbusPost',
					'NIPPON_EXPRESS'             => 'Nippon Express',
					'NORTHLINE'                  => 'Northline',
					'NYTLOGISTICS'               => 'NYT SUPPLY CHAIN LOGISTICS Co., LTD',
					'SHOPOLIVE'                  => 'Olive',
					'ONTRAC'                     => 'ONTRAC',
					'OPTIMACOURIER'              => 'Optima Courier',
					'ORANGECONNEX'               => 'Orangeconnex',
					'ORANGE_DS'                  => 'OrangeDS (Orange Distribution Solutions Inc)',
					'OZEPARTS_SHIPPING'          => 'Ozeparts Shipping',
					'P2P_TRC'                    => 'P2P TrakPak',
					'PACK_UP'                    => 'PACK-UP',
					'PACKETA'                    => 'Packeta',
					'PACKFLEET'                  => 'PACKFLEET',
					'PACKS'                      => 'Packs',
					'PAIKEDA'                    => 'Paikeda',
					'PAKAJO'                     => 'Pakajo World',
					'PANDION'                    => 'Pandion',
					'PANDU'                      => 'PANDU',
					'PANTHER_ORDER_NUMBER'       => 'Panther Order Number',
					'PARCELRIGHT'                => 'Parcel Right',
					'PARCEL_2_POST'              => 'Parcel To Post',
					'PARCELFORCE'                => 'PARCELFORCE',
					'PARCELJET'                  => 'Parceljet',
					'PARCLL'                     => 'PARCLL',
					'PAYO'                       => 'Payo',
					'PIDGE'                      => 'Pidge',
					'PIL_LOGISTICS'              => 'PIL Logistics (China) Co.',
					'PLYCONGROUP'                => 'Plycon Transportation Group',
					'POSTONE'                    => 'Post ONE',
					'POSTAPLUS'                  => 'Posta Plus',
					'POSTE_ITALIANE_PACCOCELERE' => 'Poste Italiane Paccocelere',
					'POSTEN_NORGE'               => 'Posten Norge (www.posten.no)',
					'POSTNL_INTERNATIONAL'       => 'PostNL International',
					'SWE_POSTNORD'               => 'Postnord sweden',
					'POSTPLUS'                   => 'PostPlus',
					'PROFESSIONAL_COURIERS'      => 'PROFESSIONAL COURIERS',
					'PPL'                        => 'Professional Parcel Logistics',
					'PROMEDDELIVERY'             => 'ProMed Delivery',
					'PTT_KARGO'                  => 'PTT Kargo',
					'PUROLATOR'                  => 'Purolator',
					'QTRACK'                     => 'QTrack',
					'QUIQUP'                     => 'Quiqup',
					'RELAY'                      => 'Relay',
					'RHENUS_GROUP'               => 'Rhenus Logistics',
					'RICHMOM'                    => 'Rich Mom',
					'RODONAVES'                  => 'Rodonaves',
					'ROYALSHIPMENTS'             => 'Royalshipments',
					'RUSSIAN_POST'               => 'Russian post',
					'SAGAWA'                     => 'SAGAWA',
					'SBERLOGISTICS_RU'           => 'Sber Logistics',
					'SCOTTY'                     => 'Scotty',
					'SENDEO_KARGO'               => 'Sendeo Kargo',
					'SENDING'                    => 'Sending Transporte Urgente y Comunicacion',
					'SHOWL'                      => 'SENHONG INTERNATIONAL LOGISTICS',
					'SERVIENTREGA'               => 'Servientrega',
					'SETEL'                      => 'Setel Express',
					'SF_EX'                      => 'SF Express',
					'SF_EXPRESS_CN'              => 'SF Express China',
					'SHADOWFAX'                  => 'Shadowfax',
					'SHENZHEN'                   => 'Shenzhen 1st International Logistics(group)co',
					'HOTSIN_CARGO'               => 'SHENZHEN HOTSIN CARGO INTL FORWARDING CO., LTD',
					'KWT'                        => 'Shenzhen Jinghuada Logistics Co.',
					'SHERPA'                     => 'Sherpa',
					'SHIPBOB'                    => 'Shipbob',
					'SHIPROCKET'                 => 'Shiprocket X',
					'SHIPX'                      => 'ShipX',
					'SHIPXPRES'                  => 'SHIPXPRESS',
					'SPX'                        => 'Shopee Express',
					'SPX_TH'                     => 'Shopee Xpress',
					'SHUNBANG_EXPRESS'           => 'ShunBang Express',
					'SHYPLITE'                   => 'Shypmax',
					'SIMSGLOBAL'                 => 'Sims Global',
					'SIODEMKA'                   => 'SIODEMKA',
					'SKYNET_WORLDWIDE'           => 'SkyNet Worldwide Express',
					'SKY_POSTAL'                 => 'SkyPostal',
					'SK_POSTA'                   => 'Slovenska pošta',
					'SMARTCAT'                   => 'SMARTCAT',
					'SMARTKARGO'                 => 'SmartKargo',
					'SPEEDEX'                    => 'Speedex',
					'SPREETAIL'                  => 'Spreetail',
					'SPRINT_PACK'                => 'SPRINT PACK',
					'SRT_TRANSPORT'              => 'SRT Transport',
					'STARTRACK'                  => 'Startrack',
					'STARTRACK_EXPRESS'          => 'Startrack Express',
					'STATOVERNIGHT'              => 'Stat Overnight',
					'CN_STO'                     => 'STO Express',
					'SURAT_KARGO'                => 'Surat Kargo',
					'SWE'                        => 'SWE',
					'SWIFTX'                     => 'SwiftX',
					'SWISHIP'                    => 'Swiship',
					'AMAZON_FBA_SWISHIP_IN'      => 'Swiship IN',
					'SWISS_POST'                 => 'SWISS POST',
					'T_CAT'                      => 'T-cat',
					'TW_TAIWAN_POST'             => 'Taiwan Post',
					'TAQBIN_HK'                  => 'TAQBIN Hong Kong',
					'TAQBIN_SG'                  => 'Taqbin SG',
					'TESTING_COURIER'            => 'Testing Courier',
					'TFORCE_FREIGHT'             => 'Tforce Freight',
					'THUNDEREXPRESS'             => 'Thunder Express Australia',
					'TNT_AU'                     => 'Tnt AU',
					'TNT_IT'                     => 'Tnt IT',
					'TNT_REFR'                   => 'TNT Reference',
					'TOLL_IPEC'                  => 'TOLL IPEC',
					'TOLL_PRIORITY'              => 'Toll Priority',
					'TOMYDOOR'                   => 'Tomydoor',
					'ESDEX'                      => 'Top Ideal Express',
					'TOPTRANS'                   => 'TOPTRANS',
					'THAIPARCELS'                => 'TP Logistic',
					'TRANS2U'                    => 'Trans2u',
					'TRANSMISSION'               => 'TRANSMISSION',
					'TANET'                      => 'Transport Ambientales',
					'TRANSVIRTUAL'               => 'TransVirtual',
					'TRUNKRS'                    => 'Trunkrs',
					'TRUSK'                      => 'Trusk France',
					'TUSKLOGISTICS'              => 'Tusk Logistics',
					'TYP'                        => 'TYP',
					'U_ENVIOS'                   => 'U-ENVIOS',
					'UCS'                        => 'UCS',
					'UDS'                        => 'United Delivery Service',
					'UPS'                        => 'United Parcel Service',
					'UNIUNI'                     => 'Uniuni',
					'UPARCEL'                    => 'Uparcel',
					'UPS_CHECKER'                => 'Ups Checker',
					'UPS_FREIGHT'                => 'UPS Freight',
					'UPS_REFERENCE'              => 'UPS Reference',
					'US_APC'                     => 'Us Apc',
					'VDTRACK'                    => 'VDtrack',
					'VIAXPRESS'                  => 'ViaXpress',
					'WEWORLDEXPRESS'             => 'We World Express',
					'WEEE'                       => 'Weee',
					'WELIVERY'                   => 'Welivery',
					'WESHIP'                     => 'WeShip',
					'WORLDNET'                   => 'Worldnet Logistics',
					'XYY'                        => 'Xingyunyi Logistics',
					'XPRESSBEES'                 => 'XPRESSBEES',
					'YAMATO'                     => 'YAMATO',
					'YIFAN'                      => 'YiFan Express',
					'YODEL'                      => 'Yodel',
					'YODEL_DIR'                  => 'Yodel Direct',
					'YODEL_INTNL'                => 'Yodel International',
					'YUNHUIPOST'                 => 'Yunhuipost',
					'YUSEN'                      => 'Yusen Logistics',
					'YYCOM'                      => 'Yycom',
					'YYEXPRESS'                  => 'YYEXPRESS',
					'ZTO_DOMESTIC'               => 'ZTO Express China'
				]
			],
			'AR'     => [
				'name'  => 'Argentina',
				'items' => [
					'FASTRACK' => 'Fasttrack',
					'ANDREANI' => 'Grupo logistico Andreani',
					'ARG_OCA'  => 'OCA Argentina'
				]
			],
			'AU'     => [
				'name'  => 'Australia',
				'items' => [
					'ADSONE'             => 'ADSone',
					'ALLIEDEXPRESS'      => 'Allied Express',
					'ARAMEX_AU'          => 'Aramex Australia (formerly Fastway AU)',
					'AU_AU_POST'         => 'Australia Post',
					'BLUESTAR'           => 'Blue Star',
					'BONDSCOURIERS'      => 'Bonds Courier Service (bondscouriers.com.au)',
					'BORDEREXPRESS'      => 'Border Express',
					'COPE'               => 'Cope Sensitive Freight',
					'COURIERS_PLEASE'    => 'CouriersPlease (couriersplease.com.au)',
					'DIRECTCOURIERS'     => 'Direct Couriers',
					'DIRECTFREIGHT_AU'   => 'Direct Freight Express',
					'EMEGA'              => 'Discount Post',
					'DTDC_AU'            => 'DTDC Australia',
					'ENDEAVOUR_DELIVERY' => 'Endeavour Delivery',
					'HUNTER_EXPRESS'     => 'Hunter Express',
					'ICUMULUS'           => 'ICumulus',
					'INTERPARCEL_AU'     => 'Interparcel Australia',
					'KINISI'             => 'Kinisi Transport Pty Ltd',
					'LAND_LOGISTICS'     => 'Land Logistics',
					'PARCELPOINT'        => 'Parcelpoint',
					'PFLOGISTICS'        => 'PFL',
					'SENDLE'             => 'Sendle',
					'SHIPPIT'            => 'Shippit',
					'STAR_TRACK_EXPRESS' => 'Star Track Express',
					'AUS_STARTRACK'      => 'StarTrack (startrack.com.au)',
					'TFM'                => 'TFM Xpress',
					'TIGFREIGHT'         => 'TIG Freight',
					'UBI_LOGISTICS'      => 'UBI Smart Parcel',
					'VTFE'               => 'VicTas Freight Express',
					'XL_EXPRESS'         => 'XL Express'
				]
			],
			'AT'     => [
				'name'  => 'Austria',
				'items' => [
					'AUSTRIAN_POST_EXPRESS' => 'Austrian Post',
					'AU_AUSTRIAN_POST'      => 'Austrian Post (Registered)',
					'DPD_AT'                => 'DPD Austria'
				]
			],
			'BE'     => [
				'name'  => 'Belgium',
				'items' => [
					'B_TWO_C_EUROPE'  => 'B2C courier Europe',
					'LANDMARK_GLOBAL' => 'Landmark Global',
					'MIKROPAKKET_BE'  => 'Mikropakket Belgium'
				]
			],
			'BA'     => [
				'name'  => 'Bosnia and Herzegovina',
				'items' => [
					'BH_POSTA' => 'BH Posta (www.posta.ba)'
				]
			],
			'BR'     => [
				'name'  => 'Brazil',
				'items' => [
					'BRA_CORREIOS' => 'Correios Brazil',
					'DIRECTLOG'    => 'Directlog (www.directlog.com.br)',
					'FRETERAPIDO'  => 'Frete Rapido',
					'INTELIPOST'   => 'Intelipost (TMS for LATAM)'
				]
			],
			'BG'     => [
				'name'  => 'Bulgaria',
				'items' => [
					'A1POST'            => 'A1Post',
					'BG_BULGARIAN_POST' => 'Bulgarian Posts'
				]
			],
			'KH'     => [
				'name'  => 'Cambodia',
				'items' => [
					'KHM_CAMBODIA_POST'  => 'Cambodia Post',
					'ROADRUNNER_FREIGHT' => 'Roadbull Logistics'
				]
			],
			'CA'     => [
				'name'  => 'Canada',
				'items' => [
					'AWEST'             => 'American West',
					'CA_CANADA_POST'    => 'Canada Post',
					'CHITCHATS'         => 'Chit Chats',
					'COURANT_PLUS'      => 'Courant Plus',
					'ESHIPPER'          => 'EShipper',
					'GLOBAL_ESTES'      => 'Estes Express Lines',
					'FLEETOPTICSINC'    => 'FleetOptics',
					'DICOM'             => 'GLS Logistic Systems Canada Ltd./Dicom',
					'GTAGSM'            => 'GTA GSM',
					'INTELCOM_CA'       => 'Intelcom',
					'LOOMIS_EXPRESS'    => 'Loomis Express',
					'MBW'               => 'MBW Courier Inc.',
					'NATIONEX'          => 'Nationex courier',
					'OBIBOX'            => 'Obibox',
					'AIR_CANADA_GLOBAL' => 'Rivo (Air canada)',
					'RPXLOGISTICS'      => 'RPX Logistics',
					'STALLIONEXPRESS'   => 'Stallion Express',
					'ZIINGFINALMILE'    => 'Ziing Final Mile Inc'
				]
			],
			'CL'     => [
				'name'  => 'Chile',
				'items' => [
					'BLUEX'    => 'Blue Express',
					'JAWAR'    => 'Jawar',
					'OTSCHILE' => 'OTS',
					'STARKEN'  => 'STARKEN couriers'
				]
			],
			'CN'     => [
				'name'  => 'China',
				'items' => [
					'CN_17POST'         => '17 Post Service',
					'ACSWORLDWIDE'      => 'ACS Worldwide Express',
					'CAINIAO'           => 'AliExpress Standard Shipping',
					'ANJUN'             => 'Anjun couriers',
					'ANSERX'            => 'ANSERX courier',
					'AOYUE'             => 'Aoyue',
					'BEL_RS'            => 'BEL North Russia',
					'CN_BESTEXPRESS'    => 'Best Express',
					'CN_BOXC'           => 'BoxC courier',
					'CPEX'              => 'Captain Express International',
					'CGS_EXPRESS'       => 'CGS Express',
					'CN_CHINA_POST_EMS' => 'China Post',
					'CHUKOU1'           => 'Chukou1',
					'CJPACKET'          => 'CJ Packet',
					'CLEVY_LINKS'       => 'Clevy Links',
					'CNDEXPRESS'        => 'CND Express',
					'CNEXPS'            => 'CNE Express',
					'COMET_TECH'        => 'CometTech',
					'CPACKET'           => 'Cpacket couriers',
					'CUCKOOEXPRESS'     => 'Cuckoo Express',
					'DEX_I'             => 'DEX-I courier',
					'DIDADI'            => 'DIDADI Logistics tech',
					'DPE_EXPRESS'       => 'DPE Express',
					'DTD_EXPR'          => 'DTD Express',
					'EMPS_CN'           => 'EMPS Express',
					'CN_EQUICK'         => 'Equick China',
					'ESHIPPING'         => 'Eshipping',
					'ZES_EXPRESS'       => 'Eshun international Logistic',
					'FAR_INTERNATIONAL' => 'FAR international',
					'FARGOOD'           => 'FarGood',
					'FEDEX_CHINA'       => 'Fedex',
					'FULFILLME'         => 'Fulfillme',
					'GANGBAO'           => 'GANGBAO Supplychain',
					'GESWL'             => 'GESWL Express',
					'HDB'               => 'Haidaibao',
					'HDB_BOX'           => 'Haidaibao (BOX)',
					'HH_EXP'            => 'Hua Han Logistics',
					'HUAHAN_EXPRESS'    => 'HUAHANG EXPRESS',
					'HUODULL'           => 'Huodull',
					'HX_EXPRESS'        => 'HX Express',
					'IDEXPRESS'         => 'IDEX courier',
					'INTEL_VALLEY'      => 'Intel-Valley Supply chain (ShenZhen) Co. Ltd',
					'JAWAR'             => 'Jawar',
					'J_NET'             => 'J-Net',
					'JINDOUYUN'         => 'Jindouyun Courier',
					'JOOM_LOGIS'        => 'Joom Logistics',
					'JUMPPOINT'         => 'Jumppoint',
					'K1_EXPRESS'        => 'K1 Express',
					'KY_EXPRESS'        => 'Kua Yue Express',
					'LALAMOVE'          => 'Lalamove',
					'LEADER'            => 'Leader',
					'SDH_SCM'           => 'Lightning Monkey',
					'LMPARCEL'          => 'LM Parcel',
					'LOGISTERS'         => 'Logisters',
					'LTIANEXP'          => 'LTIAN EXP',
					'LTL'               => 'LTL COURIER',
					'MORE_LINK'         => 'Morelink',
					'NANJINGWOYUAN'     => 'Nanjing Woyuan',
					'ONEWORLDEXPRESS'   => 'One World Express',
					'PADTF'             => 'Padtf',
					'PAN_ASIA'          => 'Pan-Asia International',
					'CN_PAYPAL_PACKAGE' => 'PayPal Package',
					'PFCEXPRESS'        => 'PFC Express',
					'CN_POST56'         => 'Post56',
					'HKD'               => 'Qingdao HKD International Logistics',
					'ETS_EXPRESS'       => 'RETS express',
					'RUSTON'            => 'Ruston',
					'CN_SF_EXPRESS'     => 'SF Express (www.sf-express.com)',
					'SFB2C'             => 'SF International',
					'SFC_LOGISTICS'     => 'SFC',
					'SFCSERVICE'        => 'SFC Service',
					'SFYDEXPRESS'       => 'SFYD Express',
					'DAJIN'             => 'Shanghai Aqrum Chemical Logistics Co.Ltd',
					'SHOPLINE'          => 'Shopline',
					'SINO_SCM'          => 'Sino SCM',
					'SINOTRANS'         => 'Sinotrans',
					'SPEEDAF'           => 'Speedaf Express',
					'STONE3PL'          => 'STONE3PL',
					'SYPOST'            => 'Sunyou Post',
					'SUPERPACKLINE'     => 'Super Pac Line',
					'TARRIVE'           => 'TONDA GLOBAL',
					'TOPHATTEREXPRESS'  => 'Tophatter Express',
					'TOPYOU'            => 'TopYou',
					'UC_EXPRE'          => 'Ucexpress',
					'VIWO'              => 'VIWO IoT',
					'WANBEXPRESS'       => 'WanbExpress',
					'WEASHIP'           => 'Weaship',
					'CN_WEDO'           => 'WeDo Logistics',
					'WINIT'             => 'WinIt',
					'WISE_EXPRESS'      => 'Wise Express',
					'CN_WISHPOST'       => 'WishPost',
					'XMSZM'             => 'Xmszm',
					'XQ_EXPRESS'        => 'XQ Express',
					'YANWEN'            => 'Yanwen Logistics',
					'YDH_EXPRESS'       => 'YDH express',
					'ELIAN_POST'        => 'Yilian (Elian) Supply Chain',
					'YINGNUO_LOGISTICS' => 'Yingnuo Logistics',
					'YTO'               => 'YTO Express',
					'YUNANT'            => 'Yunant',
					'CN_YUNDA'          => 'Yunda Express',
					'YUNEXPRESS'        => 'YunExpress',
					'ZJS_EXPRESS'       => 'ZJS International',
					'ZTO_EXPRESS'       => 'ZTO Express',
					'ZYOU'              => 'ZYEX'
				]
			],
			'CO'     => [
				'name'  => 'Colombia',
				'items' => [
					'COORDINADORA' => 'Coordinadora'
				]
			],
			'HR'     => [
				'name'  => 'Croatia',
				'items' => [
					'GLS_CROTIA'   => 'GLS Croatia',
					'HRV_HRVATSKA' => 'Hrvatska posta',
					'OVERSE_EXP'   => 'Overseas Express'
				]
			],
			'CY'     => [
				'name'  => 'Cyprus',
				'items' => [
					'CYPRUS_POST_CYP' => 'Cyprus Post'
				]
			],
			'CZ'     => [
				'name'  => 'Czech Republic',
				'items' => [
					'CESKA_CZ' => 'Czech Post',
					'GLS_CZ'   => 'GLS Czech Republic'
				]
			],
			'DK'     => [
				'name'  => 'Denmark',
				'items' => [
					'DANSKE_FRAGT'          => 'Danske Fragtaend',
					'POSTNORD_LOGISTICS_DK' => 'Ostnord Denmark',
					'POSTNORD_LOGISTICS'    => 'PostNord Logistics',
					'XPRESSEN_DK'           => 'Xpressen courier'
				]
			],
			'EE'     => [
				'name'  => 'Estonia',
				'items' => [
					'OMNIVA' => 'Omniva'
				]
			],
			'FI'     => [
				'name'  => 'Finland',
				'items' => [
					'MATKAHUOLTO' => 'Matkahuolto',
					'POSTI'       => 'Posti courier'
				]
			],
			'FR'     => [
				'name'  => 'France',
				'items' => [
					'CHRONOPOST_FR'           => 'Chronopost france (www.chronopost.fr)',
					'COLIS_PRIVE'             => 'Colis Privé',
					'FR_COLIS'                => 'Colissimo',
					'CUBYN'                   => 'Cubyn',
					'DPD_FR'                  => 'DPD France',
					'FR_EXAPAQ'               => 'DPD France (formerly exapaq)',
					'HEPPNER_FR'              => 'Heppner France',
					'LA_POSTE_SUIVI'          => 'La Poste',
					'SWISS_UNIVERSAL_EXPRESS' => 'Swiss Universal Express',
					'TNT_FR'                  => 'TNT France',
					'VIRTRANSPORT'            => 'VIR Transport'
				]
			],
			'DE'     => [
				'name'  => 'Germany',
				'items' => [
					'HERMES_DE'              => 'Hermes Germany',
					'AO_DEUTSCHLAND'         => 'AO Deutschland',
					'DE_DPD_DELISTRACK'      => 'DPD Germany',
					'FIEGE'                  => 'Fiege Logistics',
					'GEIS'                   => 'Geis CZ',
					'GEL_EXPRESS'            => 'Gel Express Logistik',
					'GENERAL_OVERNIGHT'      => 'Go!Express and logistics',
					'HEPPNER'                => 'Heppner Internationale Spedition GmbH & Co.',
					'HERMES_2MANN_HANDLING'  => 'Hermes Einrichtungs Service GmbH & Co. KG',
					'NOX_NACHTEXPRESS'       => 'Innight Express Germany GmbH (nox NachtExpress)',
					'LIEFERY'                => 'Liefery',
					'NOX_NIGHT_TIME_EXPRESS' => 'NOX NightTimeExpress',
					'PARCELONE'              => 'PARCEL ONE',
					'PRESSIODE'              => 'Pressio',
					'RABEN_GROUP'            => 'Raben Group',
					'STRECK_TRANSPORT'       => 'Streck Transport',
					'SWISHIP_DE'             => 'Swiship DE',
					'URBIFY'                 => 'Urbify'
				]
			],
			'GR'     => [
				'name'  => 'Greece',
				'items' => [
					'ACS_GR'           => 'ACS Courier',
					'EASY_MAIL'        => 'Easy Mail',
					'GENIKI_GR'        => 'Geniki Taxydromiki',
					'PACK_MAN'         => 'Packman',
					'SPEEDCOURIERS_GR' => 'Speed Couriers'
				]
			],
			'HK'     => [
				'name'  => 'Hong Kong',
				'items' => [
					'CFL_LOGISTICS'         => 'CFL Logistics',
					'CJ_HK_INTERNATIONAL'   => 'CJ Logistics International(Hong Kong)',
					'CLE_LOGISTICS'         => 'CL E-Logistics Solutions Limited',
					'CONTINENTAL'           => 'Continental',
					'COSTMETICSNOW'         => 'Cosmetics Now',
					'DEALERSEND'            => 'DealerSend',
					'DHL_GLOBAL_MAIL_ASIA'  => 'DHL Global Mail Asia (www.dhl.com)',
					'DHL_HK'                => 'DHL HonKong',
					'DPD_HK'                => 'DPD HongKong',
					'DTDC_EXPRESS'          => 'DTDC express',
					'GLOBAVEND'             => 'Globavend',
					'HK_POST'               => 'Hongkong Post (www.hongkongpost.hk)',
					'JANCO'                 => 'Janco Ecommerce',
					'JS_EXPRESS'            => 'JS EXPRESS',
					'KEC'                   => 'KEC courier',
					'KERRY_ECOMMERCE'       => 'Kerry eCommerce',
					'LHT_EXPRESS'           => 'LHT Express',
					'LOGISTICSWORLDWIDE_HK' => 'Logistic Worldwide Express (LWE Honkong)',
					'MAINWAY'               => 'Mainway',
					'MORNING_EXPRESS'       => 'Morning Express',
					'OKAYPARCEL'            => 'OkayParcel',
					'OMNIPARCEL'            => 'Omni Parcel',
					'PALEXPRESS'            => 'PAL Express Limited',
					'PICKUP'                => 'Pickupp',
					'QUANTIUM'              => 'Quantium',
					'SEKOLOGISTICS'         => 'SEKO Logistics',
					'SHIP_IT_ASIA'          => 'Ship It Asia',
					'SHOPLINE'              => 'Shopline',
					'SMOOTH'                => 'Smooth Couriers',
					'STEPFORWARDFS'         => 'STEP FORWARD FREIGHT SERVICE CO LTD'
				]
			],
			'HU'     => [
				'name'  => 'Hungary',
				'items' => [
					'DPD_HGRY'   => 'DPD Hungary',
					'EXPRESSONE' => 'EXPRESSONE',
					'GLS_HUN'    => 'GLS Hungary'
				]
			],
			'IN'     => [
				'name'  => 'India',
				'items' => [
					'ARIHANTCOURIER'       => 'AICS',
					'BOMBINOEXP'           => 'Bombino Express Pvt',
					'IND_DELHIVERY'        => 'Delhivery India',
					'DELIVERYONTIME'       => 'DELIVERYONTIME LOGISTICS PVT LTD',
					'DTDC_IN'              => 'DTDC India',
					'IND_ECOM'             => 'Ecom Express',
					'EKART'                => 'Ekart logistics (ekartlogistics.com)',
					'IND_FIRSTFLIGHT'      => 'First Flight Couriers',
					'IND_GATI'             => 'Gati-KWE',
					'IND_GOJAVAS'          => 'GoJavas',
					'GRANDSLAMEXPRESS'     => 'Grand Slam Express',
					'HOLISOL'              => 'Holisol',
					'LEXSHIP'              => 'LexShip',
					'OCS'                  => 'OCS ANA Group',
					'PARCELLED_IN'         => 'Parcelled.in',
					'PICKRR'               => 'Pickrr',
					'IND_SAFEEXPRESS'      => 'Safexpress',
					'SCUDEX_EXPRESS'       => 'Scudex Express',
					'SHREE_ANJANI_COURIER' => 'Shree Anjani Courier',
					'SHREE_MARUTI'         => 'Shree Maruti Courier Services Pvt Ltd',
					'SHREENANDANCOURIER'   => 'SHREE NANDAN COURIER',
					'SHREETIRUPATI'        => 'SHREE TIRUPATI COURIER SERVICES PVT. LTD.',
					'SKYKING'              => 'Sky King',
					'SPOTON'               => 'SPOTON Logistics Pvt Ltd',
					'TRACKON'              => 'Trackon Couriers Pvt. Ltd',
					'XINDUS'               => 'Xindus'
				]
			],
			'ID'     => [
				'name'  => 'Indonesia',
				'items' => [
					'CHOIR_EXP'   => 'Choir Express Indonesia',
					'INDOPAKET'   => 'INDOPAKET',
					'JX'          => 'JX courier',
					'KURASI'      => 'KURASI',
					'NINJAVAN_ID' => 'Ninja Van Indonesia',
					'MGLOBAL'     => 'PT MGLOBAL LOGISTICS INDONESIA',
					'RCL'         => 'Red Carpet Logistics',
					'SAP_EXPRESS' => 'SAP EXPRESS',
					'SIN_GLBL'    => 'Sin Global Express',
					'TIKI_ID'     => 'Tiki shipment',
					'TRANS_KARGO' => 'Trans Kargo Internasional',
					'WAHANA_ID'   => 'Wahana express (www.wahana.com)'
				]
			],
			'IE'     => [
				'name'  => 'Ireland',
				'items' => [
					'AN_POST'    => 'An Post',
					'DPD_IR'     => 'DPD Ireland',
					'FASTWAY_IR' => 'Fastway Ireland',
					'WISELOADS'  => 'Wiseloads'
				]
			],
			'IL'     => [
				'name'  => 'Israel',
				'items' => [
					'GCX'               => 'GC Express',
					'ISRAEL_POST'       => 'Israel Post',
					'ISR_POST_DOMESTIC' => 'Israel Post Domestic'
				]
			],
			'IT'     => [
				'name'  => 'Italy',
				'items' => [
					'BRT_IT_PARCELID'  => 'BRT Bartolini(Parcel ID)',
					'BRT_IT'           => 'BRT couriers Italy (www.brt.it)',
					'ARCO_SPEDIZIONI'  => 'Arco Spedizioni SP',
					'BLINKLASTMILE'    => 'Blink',
					'HRPARCEL'         => 'HR Parcel',
					'I_DIKA'           => 'I Dika',
					'INPOST_IT'        => 'InPost Italy',
					'LICCARDI_EXPRESS' => 'LICCARDI EXPRESS COURIER',
					'MILKMAN'          => 'Milkman courier',
					'IT_POSTE_ITALIA'  => 'Poste italiane (www.poste.it)',
					'SAILPOST'         => 'SAILPOST',
					'SDA_IT'           => 'SDA Italy',
					'SPEDISCI'         => 'Spedisci.online',
					'TNT_CLICK_IT'     => 'TNT-Click Italy'
				]
			],
			'JP'     => [
				'name'  => 'Japan',
				'items' => [
					'JPN_JAPAN_POST' => 'Japan Post',
					'KWE_GLOBAL'     => 'KWE Global',
					'MAIL_PLUS'      => 'MailPlus',
					'MAILPLUS_JPN'   => 'MailPlus (Japan)',
					'SWISHIP_JP'     => 'Swiship JP',
					'SEINO'          => 'Seino'
				]
			],
			'JE'     => [
				'name'  => 'Jersey',
				'items' => [
					'JERSEY_POST' => 'Jersey Post'
				]
			],
			'KR'     => [
				'name'  => 'Korea',
				'items' => [
					'CELLO_SQUARE'     => 'Cello Square',
					'CROSHOT'          => 'Croshot',
					'DOORA'            => 'Doora Logistics',
					'EPARCEL_KR'       => 'Eparcel Korea',
					'KPOST'            => 'Korea Post',
					'KR_KOREA_POST'    => 'Koreapost (www.koreapost.go.kr)',
					'KYUNGDONG_PARCEL' => 'Kyungdong Parcel',
					'LOTTE'            => 'Lotte Global Logistics',
					'RINCOS'           => 'Rincos',
					'ROCKET_PARCEL'    => 'Rocket Parcel International',
					'SHIP_GATE'        => 'ShipGate',
					'SHIPTER'          => 'SHIPTER',
					'SRE_KOREA'        => 'SRE Korea (www.srekorea.co.kr)',
					'TOLOS'            => 'Tolos courier'
				]
			],
			'LA'     => [
				'name'  => "Lao People's Democratic Republic (the)",
				'items' => [
					'LAO_POST' => 'Lao Post'
				]
			],
			'LV'     => [
				'name'  => 'Latvia',
				'items' => [
					'CDEK'           => 'CDEK courier',
					'LATVIJAS_PASTS' => 'Latvijas Pasts'
				]
			],
			'LT'     => [
				'name'  => 'Lithuania',
				'items' => [
					'VENIPAK' => 'Venipak'
				]
			],
			'MY'     => [
				'name'  => 'Malaysia',
				'items' => [
					'ABXEXPRESS_MY' => 'ABX Express',
					'MYS_AIRPAK'    => 'Airpak Express',
					'CITYLINK_MY'   => 'City-Link Express',
					'CJ_INT_MY'     => 'CJ International malaysia',
					'COLLECTCO'     => 'CollectCo',
					'EASYPARCEL'    => 'Easyparcel',
					'FMX'           => 'FMX',
					'MYS_GDEX'      => 'GDEX courier',
					'JTEXPRESS'     => 'J&T EXPRESS MALAYSIA',
					'JINSUNG'       => 'JINSUNG TRADING',
					'JOCOM'         => 'Jocom',
					'KANGAROO_MY'   => 'Kangaroo Worldwide Express',
					'LINE'          => 'Line Clear Express & Logistics Sdn Bhd',
					'M_XPRESS'      => 'M Xpress Sdn Bhd',
					'MYS_MYS_POST'  => 'Malaysia Post',
					'MATDESPATCH'   => 'Matdespatch',
					'NATIONWIDE_MY' => 'Nationwide Express Courier Services Bhd (www.nationwide.com.my)',
					'NINJAVAN_MY'   => 'Ninja Van (www.ninjavan.co)',
					'PICKUPP_MYS'   => 'PICK UPP',
					'SENDY'         => 'Sendy Express',
					'MYS_SKYNET'    => 'Skynet Malaysia',
					'WEPOST'        => 'WePost Sdn Bhd'
				]
			],
			'MX'     => [
				'name'  => 'Mexico',
				'items' => [
					'CORREOS_DE_MEXICO' => 'Correos Mexico',
					'DOMINO'            => 'DOMINO',
					'MEX_ESTAFETA'      => 'Estafeta (www.estafeta.com)',
					'GRUPO'             => 'Grupo ampm',
					'HOUNDEXPRESS'      => 'Hound Express',
					'MEX_SENDA'         => 'mexico senda express',
					'PAQUETEXPRESS'     => 'Paquetexpress',
					'MEX_REDPACK'       => 'Redpack'
				]
			],
			'NL'     => [
				'name'  => 'Netherlands',
				'items' => [
					'BROUWER_TRANSPORT' => 'Brouwer Transport en Logistiek',
					'NLD_DHL'           => 'DHL Netherland',
					'FIEGE_NL'          => 'Fiege Netherlands',
					'NLD_GLS'           => 'GLS Netherland',
					'PACKALY'           => 'Packaly',
					'PAPER_EXPRESS'     => 'Paper Express',
					'POSTNL_INTL_3S'    => 'PostNL International 3S'
				]
			],
			'NZ'     => [
				'name'  => 'New Zealand',
				'items' => [
					'CASTLEPARCELS'  => 'Castle Parcels',
					'FASTWAY_NZ'     => 'Fastway New Zealand',
					'INTERPARCEL_NZ' => 'Interparcel New Zealand',
					'MAINFREIGHT'    => 'Mainfreight',
					'NZ_NZ_POST'     => 'New Zealand Post',
					'TOLL_NZ'        => 'Toll New Zealand'
				]
			],
			'NG'     => [
				'name'  => 'Nigeria',
				'items' => [
					'NIPOST_NG' => 'NIpost (www.nipost.gov.ng)'
				]
			],
			'NO'     => [
				'name'  => 'Norway',
				'items' => [
					'HELTHJEM' => 'Helthjem'
				]
			],
			'OM'     => [
				'name'  => 'Oman',
				'items' => [
					'ASYADEXPRESS' => 'Asyad Express'
				]
			],
			'PK'     => [
				'name'  => 'Pakistan',
				'items' => [
					'FORRUN' => 'Forrun Pvt Ltd (Arpatech Venture)',
					'TCS'    => 'TCS courier'
				]
			],
			'PA'     => [
				'name'  => 'Panama',
				'items' => [
					'MULTIENTREGAPANAMA' => 'Multientrega'
				]
			],
			'PY'     => [
				'name'  => 'Paraguay',
				'items' => [
					'AEX' => 'AEX Group'
				]
			],
			'PH'     => [
				'name'  => 'Philippines',
				'items' => [
					'TWO_GO'         => '2GO Courier',
					'PHL_JAMEXPRESS' => 'Jam Express Philippines',
					'RAF_PH'         => 'RAF Philippines',
					'XPOST'          => 'Xpost.ph'
				]
			],
			'PL'     => [
				'name'  => 'Poland',
				'items' => [
					'DHL_PL'            => 'DHL Poland',
					'DPD_POLAND'        => 'DPD Poland',
					'FEDEX_POLAND'      => 'FedEx® Poland Domestic',
					'INPOST_PACZKOMATY' => 'InPost Paczkomaty',
					'PARCELSTARS'       => 'Parcelstars',
					'PL_POCZTA_POLSKA'  => 'Poczta Polska (www.poczta-polska.pl)'
				]
			],
			'PT'     => [
				'name'  => 'Portugal',
				'items' => [
					'ADICIONAL'      => 'Adicional Logistics',
					'BNEED'          => 'Bneed courier',
					'CARRIERS'       => 'Carriers courier',
					'PRT_CHRONOPOST' => 'Chronopost Portugal',
					'PRT_CTT'        => 'CTT Portugal',
					'DELNEXT'        => 'Delnext',
					'DPD_PRT'        => 'DPD Portugal'
				]
			],
			'RO'     => [
				'name'  => 'Romania',
				'items' => [
					'DPD_RO'      => 'DPD Romania',
					'GLS_ROMANIA' => 'GLS Romania',
					'POSTA_RO'    => 'Post Roman (www.posta-romana.ro)'
				]
			],
			'RU'     => [
				'name'  => 'Russia',
				'items' => [
					'BOX_BERRY'     => 'Boxberry courier',
					'CSE'           => 'CSE courier',
					'DHL_PARCEL_RU' => 'DHL Parcel Russia',
					'DOBROPOST'     => 'DobroPost',
					'DPD_RU'        => 'DPD Russia',
					'EXPRESSSALE'   => 'Expresssale',
					'GBS_BROKER'    => 'GBS-Broker',
					'PONY_EXPRESS'  => 'Pony express',
					'SHOPFANS'      => 'ShopfansRU LLC'
				]
			],
			'SA'     => [
				'name'  => 'Saudi Arabia',
				'items' => [
					'SAU_SAUDI_POST'          => 'Saudi Post',
					'SKYEXPRESSINTERNATIONAL' => 'SkyExpress Internationals',
					'SMSA_EXPRESS'            => 'SMSA Express',
					'THABIT_LOGISTICS'        => 'Thabit Logistics',
					'ZAJIL_EXPRESS'           => 'Zajil Express Company'
				]
			],
			'RS'     => [
				'name'  => 'Serbia',
				'items' => [
					'POST_SERBIA' => 'Posta Serbia'
				]
			],
			'SG'     => [
				'name'  => 'Singapore',
				'items' => [
					'CLOUDWISH_ASIA'   => 'Cloudwish Asia',
					'SG_DETRACK'       => 'Detrack',
					'FONSEN'           => 'Fonsen Logistics',
					'SIMPLYPOST'       => 'J&T Express Singapore',
					'JANIO'            => 'Janio Asia',
					'IND_JAYONEXPRESS' => 'Jayon Express (JEX)',
					'JET_SHIP'         => 'Jet-Ship Worldwide',
					'KGMHUB'           => 'KGM Hub',
					'LEGION_EXPRESS'   => 'Legion Express',
					'NHANS_SOLUTIONS'  => 'Nhans Solutions',
					'NINJAVAN_SG'      => 'Ninja van Singapore',
					'PARCELPOST_SG'    => 'Parcel Post Singapore',
					'PARKNPARCEL'      => 'Park N Parcel',
					'PICKUPP_SGP'      => 'PICK UPP (Singapore)',
					'SG_QXPRESS'       => 'Qxpress',
					'RAIDEREX'         => 'RaidereX',
					'ROADBULL'         => 'Red Carpet Logistics',
					'RZYEXPRESS'       => 'RZY Express',
					'SG_SG_POST'       => 'Singapore Post',
					'SG_SPEEDPOST'     => 'Singapore Speedpost',
					'TCK_EXPRESS'      => 'TCK Express',
					'COUREX'           => 'Urbanfox'
				]
			],
			'SK'     => [
				'name'  => 'Slovakia',
				'items' => [
					'GLS_SLOV' => 'GLS General Logistics Systems Slovakia s.r.o.'
				]
			],
			'SI'     => [
				'name'  => 'Slovenia',
				'items' => [
					'EXPRESSONE_SV' => 'EXPRESSONE',
					'GLS_SLOVEN'    => 'GLS Slovenia',
					'POST_SLOVENIA' => 'Post of Slovenia'
				]
			],
			'ZA'     => [
				'name'  => 'South Africa',
				'items' => [
					'ZA_COURIERIT'              => 'Courier IT',
					'DAWN_WING'                 => 'Dawn Wing',
					'DPE_SOUTH_AFRC'            => 'DPE South Africa',
					'INTEXPRESS'                => 'Internet Express',
					'COLLIVERY'                 => 'MDS Collivery Pty (Ltd)',
					'RAM'                       => 'RAM courier',
					'SKYNET_ZA'                 => 'Skynet World Wide Express South Africa',
					'SOUTH_AFRICAN_POST_OFFICE' => 'South African Post Office',
					'ZA_SPECIALISED_FREIGHT'    => 'Specialised Freight',
					'THECOURIERGUY'             => 'The Courier Guy'
				]
			],
			'ES'     => [
				'name'  => 'Spain',
				'items' => [
					'ASIGNA'             => 'ASIGNA courier',
					'ESP_ASM'            => 'ASM(GLS Spain)',
					'CACESA'             => 'Cacesa',
					'CBL_LOGISTICA'      => 'CBL Logistica',
					'CORREOS_EXPRESS'    => 'Correos Express',
					'DHL_PARCEL_ES'      => 'DHL parcel Spain(www.dhl.com)',
					'DHL_ES'             => 'DHL Spain(www.dhl.com)',
					'DIRMENSAJERIA'      => 'DIR',
					'ECOSCOOTING'        => 'ECOSCOOTING',
					'ESP_ENVIALIA'       => 'Envialia',
					'ENVIALIA_REFERENCE' => 'Envialia Reference',
					'MRW'                => 'MRW',
					'ESP_MRW'            => 'MRW spain',
					'NACEX_ES'           => 'NACEX Spain',
					'ESP_PACKLINK'       => 'Packlink',
					'ESP_REDUR'          => 'Redur Spain',
					'PRT_INT_SEUR'       => 'SEUR International',
					'PRT_SEUR'           => 'SEUR portugal',
					'SPRING_GDS'         => 'Spring GDS',
					'SZENDEX'            => 'SZENDEX',
					'TDN'                => 'TDN',
					'TNT_NL'             => 'THT Netherland',
					'TIPSA'              => 'TIPSA courier',
					'TNT'                => 'TNT Express',
					'GLOBAL_TNT'         => 'TNT global',
					'TOURLINE'           => 'Tourline',
					'XPO_ES'             => 'Xpo Logistics',
					'ZELERIS'            => 'Zeleris'
				]
			],
			'SE'     => [
				'name'  => 'Sweden',
				'items' => [
					'BRING'         => 'Bring',
					'DBSCHENKER_SE' => 'DB Schenker (www.dbschenker.com)',
					'DBSCHENKER_SV' => 'DB Schenker Sweden',
					'EARLYBIRD'     => 'Early Bird',
					'URB_IT'        => 'Urb-it'
				]
			],
			'CH'     => [
				'name'  => 'Switzerland',
				'items' => [
					'ASENDIA_HK' => 'Asendia HonKong',
					'PLANZER'    => 'Planzer Group',
					'VIAEUROPE'  => 'ViaEurope'
				]
			],
			'TW'     => [
				'name'  => 'Taiwan',
				'items' => [
					'CNWANGTONG'      => 'Cnwangtong',
					'CTC_EXPRESS'     => 'CTC Express',
					'DIMERCO'         => 'Dimerco Express Group',
					'HCT_LOGISTICS'   => 'HCT LOGISTICS CO.LTD.',
					'KERRYTJ'         => 'Kerry TJ Logistics',
					'PRESIDENT_TRANS' => 'PRESIDENT TRANSNET CORP',
					'GLOBAL_EXPRESS'  => 'Tai Wan Global Business'
				]
			],
			'TH'     => [
				'name'  => 'Thailand',
				'items' => [
					'ALPHAFAST'         => 'Alphafast (www.alphafast.com)',
					'CJ_KR'             => 'CJ Korea Express',
					'FASTRK_SERV'       => 'Fastrak Services',
					'FLASHEXPRESS'      => 'Flash Express',
					'NIM_EXPRESS'       => 'Nim Express',
					'NINJAVAN_THAI'     => 'Ninja van Thai',
					'SENDIT'            => 'Sendit',
					'SKYBOX'            => 'SKYBOX',
					'THA_THAILAND_POST' => 'Thailand Post (www.thailandpost.co.th)'
				]
			],
			'TR'     => [
				'name'  => 'Turkey',
				'items' => [
					'ASE'           => 'ASE KARGO',
					'BARSAN'        => 'Barsan Global Lojistik',
					'CDEK_TR'       => 'CDEK TR',
					'NAVLUNGO'      => 'Navlungo',
					'PTS'           => 'PTS courier',
					'PTT_POST'      => 'PTT Post',
					'SHIPENTEGRA'   => 'ShipEntegra',
					'YURTICI_KARGO' => 'Yurtici Kargo'
				]
			],
			'UA'     => [
				'name'  => 'Ukraine',
				'items' => [
					'NOVA_POSHTA_INT' => 'Nova Poshta (International)',
					'NOVA_POSHTA'     => 'Nova Poshta (novaposhta.ua)',
					'POSTA_UKR'       => 'UkrPoshta'
				]
			],
			'AE'     => [
				'name'  => 'United Arab Emirates',
				'items' => [
					'IBEONE'         => 'Beone Logistics',
					'ONECLICK'       => 'One click delivery services',
					'SKYNET_UAE'     => 'SKYNET UAE',
					'TEAMEXPRESSLLC' => 'Team Express Service LLC'
				]
			],
			'GB'     => [
				'name'  => 'United Kingdom',
				'items' => [
					'AMAZON'                  => 'Amazon Shipping',
					'AO_COURIER'              => 'AO Logistics',
					'APC_OVERNIGHT'           => 'APC overnight (apc-overnight.com)',
					'APC_OVERNIGHT_CONNUM'    => 'APC Overnight Consignment',
					'APG'                     => 'APG eCommerce Solutions',
					'ARK_LOGISTICS'           => 'ARK Logistics',
					'GB_ARROW'                => 'Arrow XL',
					'ASENDIA_UK'              => 'Asendia UK',
					'BIRDSYSTEM'              => 'BirdSystem',
					'BLUECARE'                => 'Bluecare Express Ltd',
					'CAE_DELIVERS'            => 'CAE Delivers',
					'DAIGLOBALTRACK'          => 'DAI Post',
					'DELTEC_UK'               => 'Deltec Courier',
					'DIAMOND_EUROGISTICS'     => 'Diamond Eurogistics Limited',
					'DMS_MATRIX'              => 'DMSMatrix',
					'DPD_LOCAL'               => 'DPD Local',
					'DPD_LOCAL_REF'           => 'DPD Local reference',
					'EU_FLEET_SOLUTIONS'      => 'EU Fleet Solutions',
					'FEDEX_UK'                => 'FedEx® UK',
					'FURDECO'                 => 'Furdeco',
					'GBA'                     => 'GBA Services Ltd',
					'GEMWORLDWIDE'            => 'GEM Worldwide',
					'HERMES'                  => 'HermesWorld UK',
					'HOME_DELIVERY_SOLUTIONS' => 'Home Delivery Solutions Ltd',
					'INTERPARCEL_UK'          => 'Interparcel UK',
					'MYHERMES'                => 'MyHermes UK',
					'NATIONAL_SAMEDAY'        => 'National Sameday',
					'GB_NORSK'                => 'Norsk Global',
					'OCS_WORLDWIDE'           => 'OCS WORLDWIDE',
					'PALLETWAYS'              => 'Palletways',
					'GB_PANTHER'              => 'Panther',
					'PANTHER_REFERENCE'       => 'Panther Reference',
					'PARCEL2GO'               => 'Parcel2Go',
					'PARCELINKLOGISTICS'      => 'Parcelink Logistics',
					'RHENUS_UK'               => 'Rhenus Logistics UK',
					'ROYAL_MAIL'              => 'Royal Mail',
					'RPD2MAN'                 => 'RPD2man Deliveries',
					'SKYNET_UK'               => 'Skynet UK',
					'AMAZON_FBA_SWISHIP'      => 'Swiship UK',
					'THEDELIVERYGROUP'        => 'TDG – The Delivery Group',
					'PALLET_NETWORK'          => 'The Pallet Network',
					'TNT_UK'                  => 'TNT UK Limited (www.tnt.com)',
					'TNT_UK_REFR'             => 'TNT UK Reference',
					'GB_TUFFNELLS'            => 'Tuffnells Parcels Express',
					'TUFFNELLS_REFERENCE'     => 'Tuffnells Parcels Express- Reference',
					'UK_UK_MAIL'              => 'UK mail (ukmail.com)',
					'WHISTL'                  => 'Whistl',
					'WNDIRECT'                => 'WnDirect',
					'UK_XDP'                  => 'XDP Express',
					'XDP_UK_REFERENCE'        => 'XDP Express Reference',
					'XPERT_DELIVERY'          => 'Xpert Delivery',
					'UK_YODEL'                => 'Yodel (www.yodel.co.uk)'
				]
			],
			'US'     => [
				'name'  => 'United States',
				'items' => [
					'3PE_EXPRESS'          => '3PE Express',
					'ADUIEPYLE'            => 'A Duie Pyle',
					'AAA_COOPER'           => 'AAA Cooper',
					'GLOBAL_ABF'           => 'ABF Freight',
					'AERONET'              => 'Aeronet couriers',
					'AGILITY'              => 'Agility',
					'ALWAYS_EXPRESS'       => 'Always Express',
					'ANICAM_BOX'           => 'ANICAM BOX EXPRESS',
					'AQUILINE'             => 'Aquiline',
					'AGSYSTEMS'            => 'Associate Global Systems',
					'BOND'                 => 'Bond courier',
					'BRAUNSEXPRESS'        => "Braun's Express",
					'BRINGER'              => 'Bringer Parcel Services',
					'CHAMPION_LOGISTICS'   => 'Champion Logistics',
					'CON_WAY'              => 'Con-way Freight',
					'DAYTON_FREIGHT'       => 'Dayton Freight',
					'DESTINY'              => 'Destiny Transportation',
					'DHL_SUPPLY_CHAIN'     => 'DHL Supply Chain APAC',
					'ECHO'                 => 'Echo courier',
					'EPST_GLBL'            => 'EPost Global',
					'FDSEXPRESS'           => 'FDSEXPRESS',
					'FEDEX'                => 'Fedex',
					'FEDEX_CROSSBORDER'    => 'FedEx Cross Border',
					'FEDEX_INTL_MLSERV'    => 'FedEx International MailService',
					'FIRSTMILE'            => 'FirstMile',
					'FREIGHTQUOTE'         => 'Freightquote by C.H. Robinson',
					'FULFILLA'             => 'Fulfilla',
					'GLS_US'               => 'GLS USA',
					'GLOBALTRANZ'          => 'GlobalTranz',
					'GSI_EXPRESS'          => 'GSI EXPRESS',
					'GSO'                  => 'GSO(GLS-USA)',
					'HIPSHIPPER'           => 'Hipshipper',
					'GLOBAL_IPARCEL'       => 'I Parcel',
					'DESCARTES'            => 'Innovel courier',
					'IORDIRECT'            => 'IOR Direct Solutions',
					'US_LASERSHIP'         => 'LaserShip',
					'LEMAN'                => 'Leman',
					'LONESTAR'             => 'Lone Star Overnight',
					'MAERGO'               => 'Maergo',
					'MAILAMERICAS'         => 'MailAmericas',
					'MEDLINE'              => 'Medline',
					'NEWEGGEXPRESS'        => 'Newegg Express',
					'OAKH'                 => 'Oakh Harbour Freight Lines',
					'US_OLD_DOMINION'      => 'Old Dominion Freight Line',
					'OSM_WORLDWIDE'        => 'OSM Worldwide',
					'PIGGYSHIP'            => 'PIGGYSHIP',
					'PILOT_FREIGHT'        => 'Pilot Freight Services',
					'PITNEY_BOWES'         => 'Pitney Bowes',
					'PITTOHIO'             => 'PITT OHIO',
					'QWINTRY'              => 'Qwintry Logistics',
					'RL_US'                => 'RL Carriers',
					'RPM'                  => 'RPM',
					'SAIA_FREIGHT'         => 'Saia LTL Freight',
					'SHIPGLOBAL_US'        => 'ShipGlobal',
					'SMTL'                 => 'Southwestern Motor Transport. Inc',
					'SEFL'                 => 'Southeastern Freight Lines',
					'SPEEDEE'              => 'Spee-Dee Delivery',
					'SPEEDX'               => 'SpeedX',
					'SUTTON'               => 'Sutton Transport',
					'TAZMANIAN_FREIGHT'    => 'Tazmanian Freight Systems',
					'TFORCE_FINALMILE'     => 'TForce Final Mile',
					'TRANSPAK'             => 'Transpak Inc.',
					'TRUMPCARD'            => 'TRUMPCARD LLC',
					'USPS'                 => 'United States Postal Service',
					'UPS_MAIL_INNOVATIONS' => 'UPS Mail Innovations',
					'USF_REDDAWAY'         => 'USF Reddaway',
					'USHIP'                => 'UShip courier',
					'VEHO'                 => 'Veho',
					'VESYL'                => 'Vesyl',
					'WESTGATE_GL'          => 'Westgate Global',
					'WINESHIPPING'         => 'Wineshipping',
					'WIZMO'                => 'Wizmo',
					'XGS'                  => 'XGS',
					'XPO_LOGISTICS'        => 'XPO logistics',
					'YAKIT'                => 'Yakit courier',
					'YOUPARCEL'            => 'YouParcel',
					'US_YRC'               => 'YRC courier',
					'ZINC'                 => 'Zinc courier'
				]
			],
			'UY'     => [
				'name'  => 'Uruguay',
				'items' => [
					'CORREO_UY' => 'Correo Uruguayo'
				]
			],
			'VE'     => [
				'name'  => 'Venezuela',
				'items' => [
					'ZOOM_RED' => 'Zoom'
				]
			],
			'VN'     => [
				'name'  => 'Vietnam',
				'items' => [
					'JTEXPRESS_VN'     => 'J&T Express Vietnam',
					'KERRYTTC_VN'      => 'Kerry Express (Vietnam) Co Ltd',
					'NTLOGISTICS_VN'   => 'Nhat Tin Logistics',
					'NINJAVAN_VN'      => 'Ninjavan Vietnam',
					'SGLINK'           => 'SG LINK',
					'VNM_VIETNAM_POST' => 'Vietnam Post',
					'VNM_VIETTELPOST'  => 'ViettelPost'
				]
			],
			'other'  => [
				'name'  => 'Other',
				'items' => [
					'OTHER' => 'Other'
				]
			]
		] );
	}

	public static function get_shipping_statuses() {
		return [
			Tracker::SHIPPED   => __( 'Shipped', 'pymntpl-paypal-woocommerce' ),
			Tracker::ON_HOLD   => __( 'On Hold', 'pymntpl-paypal-woocommerce' ),
			Tracker::DELIVERED => __( 'Delivered', 'pymntpl-paypal-woocommerce' ),
			Tracker::CANCELLED => __( 'Canceled', 'pymntpl-paypal-woocommerce' )
		];
	}

	public static function get_tracking_types() {
		return [
			Tracker::CARRIER_PROVIDED     => __( 'Carrier Provided', 'pymntpl-paypal-woocommerce' ),
			Tracker::E2E_PARTNER_PROVIDED => __( 'Marketplace', 'pymntpl-paypal-woocommerce' ),
		];
	}

}