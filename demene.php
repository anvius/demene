<?php

    const URL_BASE = 'http://www.demene.com';

    const REGEX_CURRENT_THREAD = '/http:\/\/www.demene.com\/discussion\/(\d+)\/lista-de-ventas-de-dominios([a-z0-9\-]+)\//';
    const REGEX_YEAR = '/\<title\>.*([\d]{4}).*\<\/title\>/';
    const REGEX_PAGES = '/(\d+)\<\/a\>\<\/li\>[\s\r\n]*\<li\>\<a href=\".*\"\>\&\#62;/';
    const REGEX_DOMAIN = '/([\w\d\-áéíóúñÁÉÍÓÚÑ]+)([\w\.]+)\s*(\*)?\s*\|\s*([A-Za-z€\$]*)?\s*([\d\.,]+)\s*([A-Za-z€\$]*)?\s*\|\s*([\w\d\-_,\.\sáéíóúñÁÉÍÓÚÑ]+)\s*\|\s*([\w\d\-_,\.áéíóúñÁÉÍÓÚÑ]+)/';

    function getDomainsInfo($text, $year)
    {
        $r = preg_match_all(REGEX_DOMAIN, $text, $matches, PREG_SET_ORDER);
        $results = [];
        if ($r !== false) {
            foreach ($matches as $dominio) {
                $dominio[7] = preg_replace('/[\/\-\_]/', ',', $dominio[7]);
                $results[] = [
                    'dominio' => strtolower($dominio[1] . $dominio[2]),
                    'keyword' => strtolower($dominio[1]),
                    'extension' => strtolower($dominio[2]),
                    'spanish' => ($dominio[3] != '*'),
                    'precio' => (int) preg_replace('/[\.,]/', '', $dominio[5]),
                    'moneda' => strtolower($dominio[4].$dominio[6]),
                    'plataforma' => strtolower($dominio[7]),
                    'mes' => strtolower($dominio[8]),
                    'año' => $year,
                    'cadena' => $dominio[0]
                ];
            }
            return ($results);
        }
        return ([]);
    }

    function discoverThreads()
    {
        $base = file_get_contents(URL_BASE);
        preg_match(REGEX_CURRENT_THREAD, $base, $thread);

        $threads_page = file_get_contents($thread[0]);
        preg_match_all(REGEX_CURRENT_THREAD, $threads_page, $pages);

        $urls = array_unique($pages[0]);
        sort($urls);

        foreach ($urls as $page) {
            $page_content = file_get_contents($page);
            preg_match(REGEX_YEAR, $page_content, $year);
            $year = isset($year[1]) ? $year[1] : 2010;
            preg_match(REGEX_PAGES, $page_content, $pages);
            $pages = $pages[1];
            $result[] = [
                'url' => $page,
                'year' => $year,
                'pages' => $pages
            ];
        }
        return($result);
    }

    function getDomainPages($url)
    {
        $sections = explode('/lista', $url['url']);
        $pages_urls = [];
        for($i = 1; $i < $url['pages']; $i++) {
            $pages_urls[] = $sections[0] . '/' . $i . '/lista' . $sections[1];
        }
        return($pages_urls);
    }

    function convertDate($month, $year) {
        $meses = [
            'enero',
            'febrero',
            'marzo',
            'abril',
            'mayo',
            'junio',
            'julio',
            'agosto',
            'septiembre',
            'octubre',
            'noviembre',
            'diciembre'
        ];
        $mes = 1;
        foreach($meses as $number => $name) {
            if ($month == $name) {
                $mes = $number;
                break;
            }
        }
        return(mktime(0, 0, 0, $mes, 1, $year));
    }

    function normalizeCurrency($symbol) {
        $currencies = [
            'EUR' => [
                'eur',
                'euros',
                'euro',
                '€'
            ],
            'USD' => [
                'dolares',
                'dolar',
                'usd',
                'u$d',
                'dollares',
                'dollar',
                'dollars',
                '$'
            ]
        ];
        $result = 'NULL';
        foreach($currencies as $key => $variations) {
            if(in_array($symbol,$variations)) {
                $result = $key;
                break;
            }
        }
        return($result);
    }

    if($argc != 2) {
        echo("Falta parámetro de fichero. Uso:\nphp demene.php <fichero_csv>\n");
        die();
    }

    echo("Descubriendo URLs de años...\n");

    $urls = discoverThreads();

    echo("Años encontradas: " . count($urls) . "\n");

    $domains = [];

    foreach($urls as $url) {
        echo("Revisando...\n\tAño:" . $url['year'] . "\n\tPáginas: " . $url['pages'] . "\n");
        $pages = getDomainPages($url);
        $contador_paginas = 0;
        if($url['year'] > 2010) {
            foreach($pages as $page) {
                $doms = getDomainsInfo(file_get_contents($page), $url['year']);
                echo("\t\tPágina " . ++$contador_paginas . ". Dominios encontrados: " . count($doms) . "\n");
                $domains = array_merge($domains, $doms);
            }
        }
    }

    function filterRepeted($e) {
        static $dominios_revisados = [];
        $hash = $e['dominio'] . $e['precio'];
        if(!in_array($hash, $dominios_revisados)) {
            $dominios_revisados[] = $hash;
            return(TRUE);
        }
        return(FALSE);
    }

    //$domains = array_map('filterRepeted', $domains);

    $content_to_save = "DOMINIO,KEYWORD,EXTENSION,PRECIO,MONEDA,FECHA,ETIQUETAS,MERCADO ESPAÑOL\n";

    foreach($domains as $domain) {
        $moneda = normalizeCurrency($domain['moneda']);
        $content_to_save .=
            $domain['dominio'] . ',' .
            $domain['keyword'] . ',' .
            $domain['extension'] . ',' .
            $domain['precio'] . ',' .
            $moneda . ',' .
            convertDate($domain['mes'], $domain['año']) . ',' .
            str_replace(['-', '/','_'],';', $domain['plataforma']) . ',' .
            ($domain['spanish'] ? 1 : 0) . "\n";
    }

    file_put_contents($argv[1], $content_to_save);
