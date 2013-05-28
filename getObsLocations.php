<?php
  set_time_limit(0);

  include_once('util.php');

  $config    = $argv[1];
  $bbox      = explode(',',$argv[2]);
  $providers = explode(',',$argv[3]);
  $tUom      = 'english';

  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');

  $swe2Providers = array(
    'GLOS' => array(
      'sos'      => array(
         'GetCapabilities' => 'http://64.9.200.113:8080/52n-sos-ioos/sos?service=SOS&request=GetCapabilities'
        ,'network'         => 'urn:ioos:network:glos:all'
        ,'DescribeSensor'  => array()
        ,'GetObservation'  => array()
        ,'procedures'      => array()
      )
      ,'varMap'  => array(
         'Air Temperature'                     => 'AirTemperature'
        ,'Sea Surface Wave Significant Height' => 'SignificantWaveHeight'
        ,'Sea Surface Wind Wave To Direction'  => 'WindWaveDirection'
        ,'Wind Speed'                          => 'WindSpeed'
        ,'Wind From Direction'                 => 'WindDirection'
        ,'Sea Water Temperature'               => 'WaterTemperature'
      )
      ,'provUrl' => 'http://data.glos.us/portal/xsl2html.php?xsl=xsl/glos.iso.xsl&id=wmo%3A___SITE___&url=http%3A%2F%2Fuser%3Aglos%4064.9.200.121%3A8984%2Frest%2Fglos'
    )
  );

  $sosProviders = array(
    'NDBC' => array(
       'getCaps'   => 'http://sdf.ndbc.noaa.gov/sos/server.php?VERSION=1.0.0&SERVICE=SOS&REQUEST=GetCapabilities'
      ,'variables' => array(
         'winds'                 => 'WindSpeed'
        ,'waves'                 => 'SignificantWaveHeight'
        ,'sea_water_temperature' => 'WaterTemperature'
        // ,'air_temperature'       => 'AirTemperature'
      )
      ,'provUrl'   => 'http://www.ndbc.noaa.gov/station_page.php?station=___SITE___'
      ,'siteType'  => 'buoy'
    )
    ,'COOPS' => array(
       'getCaps'   => 'http://opendap.co-ops.nos.noaa.gov/ioos-dif-sos/SOS?service=SOS&request=GetCapabilities'
      ,'variables' => array(
         'winds'                                      => 'WindSpeed'
        ,'sea_water_temperature'                      => 'WaterTemperature'
        ,'water_surface_height_above_reference_datum' => 'WaterLevel'
        ,'air_temperature'                            => 'AirTemperature'
      )
      ,'provUrl'   => 'http://tidesandcurrents.noaa.gov/geo.shtml?location=___SITE___'
      ,'siteType'  => 'buoy'
    )
  );

  if (!in_array('swe2',$providers)) {
    $swe2Providers = array();
  }

  if (!in_array('sos',$providers)) {
    $sosProviders = array();
  }

  $wmlProviders = array(
    'USGS' => array(
       'varMap'   => array(
         'Temperature, water, degrees Celsius'  => 'WaterTemperature'
        ,'Temperature, air, degrees Fahrenheit' => 'AirTemperature'
        ,'Discharge, cubic feet per second'     => 'Streamflow'
        ,'Turbidity, water, unfiltered, monochrome near infra-red LED light, 780-900 nm, detection angle 90 +-2.5 degrees, formazin nephelometric units (FNU)' => 'Turbidity'
        ,'Dissolved oxygen, water, unfiltered, milligrams per liter' => 'DissolvedOxygen'
      )
      ,'provUrl'  => 'http://waterdata.usgs.gov/usa/nwis/uv?site_no=___SITE___'
      ,'siteType' => 'station'
    )
  );
  if (!in_array('wml',$providers)) {
    $wmlProviders = array();
  }

  $metarProviders = array(
    'NWS' => array(
       'varMap'   => array(
         'wind_speed_kt'    => 'WindSpeed'
        ,'wind_dir_degrees' => 'WindDirection'
        ,'temp_c'           => 'AirTemperature'
      )
      ,'provUrl'  => 'http://www.weather.gov/obhistory/___SITE___.html'
      ,'siteType' => 'station'
    )
  );
  if (!in_array('metar',$providers)) {
    $metarProviders = array();
  }

  $ndbcTextProviders = array(
    'Environment Canada' => array(
       'varMap'   => array(
         'WSPD' => 'WindSpeed'
        ,'WDIR' => 'WindDirection'
        ,'ATMP' => 'AirTemperature'
        ,'WVHT' => 'SignificantWaveHeight'
        ,'WTMP' => 'WaterTemperature'
        ,'MWD'  => 'WindWaveDirection'
      )
      ,'provUrl'   => 'http://www.ndbc.noaa.gov/station_page.php?station=___SITE___'
      ,'siteType'  => 'buoy'
      ,'stations'  => array('45159','45135','45147','45139')
    )
  );
  if (!in_array('ndbcText',$providers)) {
    $ndbcTextProviders = array();
  }

  $soapProviders = array(
    'National Estuarine Research Reserve System' => array(
      'varMap'   => array(
         'Temp' => array('name' => 'WaterTemperature','uom' => 'deg C')
        ,'SpCond' => array('name' => 'SpecificConductivity','uom' => 'mS/cm')
        ,'Sal' => array('name' => 'Salnity','uom' => 'ppt')
        ,'DO_pct' => array('name' => 'DissolvedOxygen','uom' => '%')
        ,'DO_mgl' => array('name' => 'DissolvedOxygenConcentration','uom' => 'mg/L')
        ,'Depth' => array('name' => 'Depth','uom' => 'm')
        ,'cDepth' => array('name' => 'cDepth','uom' => 'm')
        ,'Level' => array('name' => 'Level','uom' => 'm')
        ,'cLevel' => array('name' => 'cLevel','uom' => 'm')
        ,'pH' => array('name' => 'pH','uom' => 'standard units')
        ,'Turb' => array('name' => 'Turbidity','uom' => 'NTU')
        ,'ChlFluor' => array('name' => 'ChlorophyllFlourescence','uom' => 'ug/L')
        ,'ATemp' => array('name' => 'AirTemperature','uom' => 'deg C')
        ,'RH' => array('name' => 'RelativeHumidity','uom' => '%')
        ,'BP' => array('name' => 'BarometricPressure','uom' => 'mb')
        ,'WSpd' => array('name' => 'WindSpeed','uom' => 'm/s')
        ,'MaxWSpd' => array('name' => 'MaxWindSpeed','uom' => 'm/s')
        ,'MaxWSpdT' => array('name' => 'MaxWindSpeedTime','uom' => 'hh:mm')
        ,'Wdir' => array('name' => 'WindDirection','uom' => 'deg')
        ,'SDWDir' => array('name' => 'WindDirectionSD','uom' => 'sd')
        ,'TotPAR' => array('name' => 'TotalPhotoRadiation','uom' => 'millimoles / m-2')
        ,'TotPrcp' => array('name' => 'TotalPrecipitation','uom' => 'mm')
        ,'CumPrcp' => array('name' => 'CumulativePrecipitation','uom' => 'mm')
        ,'TotSoRad' => array('name' => 'TotalSolarRadiation','uom' => 'w/m^2')
        ,'PO4F' => array('name' => 'Orthophosphate','uom' => 'mg/L')
        ,'NH4F' => array('name' => 'Ammonium','uom' => 'mg/L')
        ,'NO2F' => array('name' => 'Nitrite','uom' => 'mg/L')
        ,'NO3F' => array('name' => 'Nitrate','uom' => 'mg/L')
        ,'NO23F' => array('name' => 'NitritePlusNitrate','uom' => 'mg/L')
        ,'CHLA_N' => array('name' => 'ChlorophyllA','uom' => 'ug/L')
      )
      ,'provUrl'   => 'http://nerrsdata.org/get/realTime.cfm?stationCode=___SITE___'
      ,'siteType'  => 'station'
    )
  );
  if (!in_array('soap',$providers)) {
    $soapProviders = array();
  }

  date_default_timezone_set('UTC');
  $dNow   = time();
  $dEnd   = date('Y-m-d\TH:i:00\Z',$dNow);
  $hours  = 48;
  $dBegin = date('Y-m-d\TH:i:00\Z',$dNow - 60 * 60 * ($hours * 1 + 1));

  $sites = array();

  $counties = array();
  $s = explode("\n",file_get_contents('xml/counties.'.implode($bbox,',')));
  for ($i = 0; $i < count($s); $i++) {
    if (preg_match("/^ *(\d+)/",$s[$i],$matches)) {
      array_push($counties,$matches[1]);
    }
  }

  foreach ($wmlProviders as $provider => $v) {
    switch ($provider) {
      case 'USGS' :
        getUSGS($wmlProviders,$provider,$counties,$dBegin,$dEnd,$tUom,$sites);
      break;
    }
  }

  foreach ($ndbcTextProviders as $provider => $v) {
    switch ($provider) {
      case 'Environment Canada' :
        getNDBCText($ndbcTextProviders,$provider,$dBegin,$tUom,$sites);
      break;
    }
  }

  foreach ($soapProviders as $provider => $v) {
    switch ($provider) {
      case 'National Estuarine Research Reserve System' :
        getSoap($soapProviders,$provider,$bbox,$dBegin,$dEnd,$tUom,$sites);
      break;
    }
  }

  foreach ($metarProviders as $provider => $v) {
    switch ($provider) {
      case 'NWS' :
        getNWS($metarProviders,$provider,$bbox,$hours,$tUom,$sites);
      break;
    }
  }

  foreach ($swe2Providers as $provider => $xmlLoc) {
    print $swe2Providers[$provider]['sos']['GetCapabilities']."\n";
    $xml = @simplexml_load_file($swe2Providers[$provider]['sos']['GetCapabilities']);
    if ($xml !== FALSE) {
      foreach (
        $xml
        ->children('http://www.opengis.net/ows/1.1')->{'OperationsMetadata'}[0]
        ->children('http://www.opengis.net/ows/1.1')->{'Parameter'}
      as $parameter) {
        if (sprintf("%s",$parameter->attributes()->{'name'}) == 'service') {
          $swe2Providers[$provider]['sos']['service'] = sprintf("%s",$parameter->children('http://www.opengis.net/ows/1.1')->{'AllowedValues'}[0]->children('http://www.opengis.net/ows/1.1')->{'Value'});
        }
        else if (sprintf("%s",$parameter->attributes()->{'name'}) == 'version') {
          $swe2Providers[$provider]['sos']['version'] = sprintf("%s",$parameter->children('http://www.opengis.net/ows/1.1')->{'AllowedValues'}[0]->children('http://www.opengis.net/ows/1.1')->{'Value'});
        }
      }
      foreach (
        $xml
        ->children('http://www.opengis.net/ows/1.1')->{'OperationsMetadata'}[0]
        ->children('http://www.opengis.net/ows/1.1')->{'Operation'}
      as $operation) {
        $operationString= sprintf("%s",$operation->attributes()->{'name'});
        if (preg_match('/DescribeSensor|GetObservation/',$operationString)) {
          $swe2Providers[$provider]['sos'][$operationString]['href'] = sprintf("%s",$operation->children('http://www.opengis.net/ows/1.1')->{'DCP'}[0]->children('http://www.opengis.net/ows/1.1')->{'HTTP'}[0]->children('http://www.opengis.net/ows/1.1')->{'Get'}->attributes('http://www.w3.org/1999/xlink')->{'href'});
          foreach ($operation->children('http://www.opengis.net/ows/1.1')->{'Parameter'} as $parameter) {
            $format = sprintf("%s",$parameter->attributes()->{'name'});
            if (preg_match('/outputFormat|responseFormat/',$format)) {
              $s = sprintf("%s",$parameter->children('http://www.opengis.net/ows/1.1')->{'AllowedValues'}[0]->children('http://www.opengis.net/ows/1.1')->{'Value'});
              if (preg_match('/xml/',$s)) {
                $swe2Providers[$provider]['sos'][$operationString][$format] = $s;
              }
            }
          }
        }
      }

      // get the network procedure -- there should be only one!
      foreach (
        $xml
        ->children('http://www.opengis.net/sos/1.0')->{'Contents'}[0]
        ->children('http://www.opengis.net/sos/1.0')->{'ObservationOfferingList'}[0]
        ->children('http://www.opengis.net/sos/1.0')->{'ObservationOffering'}
      as $offering) {
        if ($offering->attributes('http://www.opengis.net/gml')->{'id'} == $swe2Providers[$provider]['sos']['network']) {
          foreach ($offering->children('http://www.opengis.net/sos/1.0') as $node) {
            if ($node->getName() == 'procedure') {
              $s = sprintf("%s",$node->attributes('http://www.w3.org/1999/xlink')->{'href'});
              if (preg_match('/^urn:ioos:network:/',$s)) {
                $swe2Providers[$provider]['sos']['procedures']['network'] = $s;
              }
            }
          }
        }
      }
    }
    else {
      unset($swe2Providers[$provider]);
    }
  }

  // pull out the stations from the networks
  foreach ($swe2Providers as $provider => $xmlLoc) {
    $u = $swe2Providers[$provider]['sos']['DescribeSensor']['href']
      .'&request=DescribeSensor'
      .'&service='.$swe2Providers[$provider]['sos']['service']
      .'&version='.$swe2Providers[$provider]['sos']['version']
      .'&outputformat='.$swe2Providers[$provider]['sos']['DescribeSensor']['outputFormat']
      .'&procedure='.$swe2Providers[$provider]['sos']['procedures']['network'];
    print "\t".$u."\n";
    $swe2Providers[$provider]['sos']['procedures']['stations'] = array();
    $xml = @simplexml_load_file($u);
    foreach (
      $xml
      ->children('http://www.opengis.net/sensorML/1.0.1')->{'member'}[0]
      ->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]
      ->children('http://www.opengis.net/sensorML/1.0.1')->{'components'}[0]
      ->children('http://www.opengis.net/sensorML/1.0.1')->{'ComponentList'}[0]
      ->children('http://www.opengis.net/sensorML/1.0.1')->{'component'}
    as $component) {
      $s = sprintf("%s",$component->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'identification'}->attributes('http://www.w3.org/1999/xlink')->{'href'});
      if (preg_match('/^urn:ioos:station:/',$s)) {
        $swe2Providers[$provider]['sos']['procedures']['stations'][$s] = array();
      }
    }
  }

  // pull out the sensors from the stations
  foreach ($swe2Providers as $provider => $xmlLoc) {
    $u = $swe2Providers[$provider]['sos']['DescribeSensor']['href']
      .'&request=DescribeSensor'
      .'&service='.$swe2Providers[$provider]['sos']['service']
      .'&version='.$swe2Providers[$provider]['sos']['version']
      .'&outputformat='.$swe2Providers[$provider]['sos']['DescribeSensor']['outputFormat']
      .'&procedure=';
    foreach (array_keys($swe2Providers[$provider]['sos']['procedures']['stations']) as $k) {
      $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['info'] = array();
      print "\t\t".$u.$k."\n";
      $xml = @simplexml_load_file($u.$k);
      foreach (
        $xml
        ->children('http://www.opengis.net/sensorML/1.0.1')->{'member'}[0]
        ->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}
      as $system) {
        // get station id info
        foreach (
          $system
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'identification'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'IdentifierList'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'identifier'}
        as $identifier) {
          $name  = sprintf("%s",$identifier->attributes()->{'name'});
          $value = sprintf("%s",$identifier->children('http://www.opengis.net/sensorML/1.0.1')->{'Term'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'value'});
          if (preg_match('/Name/',$name)) {
            $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['info'][$name] = $value;
          }
        }
        // get classification info
        foreach (
          $system
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'classification'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'ClassifierList'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'classifier'}
        as $classifier) {
          $name = sprintf("%s",$classifier->attributes()->{'name'});
          if ($name == 'platformType') {
            $value = sprintf("%s",$classifier->children('http://www.opengis.net/sensorML/1.0.1')->{'Term'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'value'});
            $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['info'][$name] = $value;
          }
        }
        // get publisher contact info
        foreach (
          $system
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'contact'}
        as $contact) {
          if (preg_match('/publisher$/',sprintf("%s",$contact->attributes('http://www.w3.org/1999/xlink')->{'role'}))) {
            $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['info']['publisher'] = array(
               'name' => sprintf("%s",$contact->children('http://www.opengis.net/sensorML/1.0.1')->{'ResponsibleParty'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'organizationName'})
              ,'href' => sprintf("%s",$contact->children('http://www.opengis.net/sensorML/1.0.1')->{'ResponsibleParty'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'contactInfo'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'onlineResource'}->attributes('http://www.w3.org/1999/xlink')->{'href'})
            );
          }
        }
        // get location info
        $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['info']['location'] = sprintf("%s",$system->children('http://www.opengis.net/sensorML/1.0.1')->{'location'}[0]->children('http://www.opengis.net/gml')->{'Point'}[0]->children('http://www.opengis.net/gml')->{'pos'});
        // get real sensors
        $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['sensors'] = array();
        foreach (
          $system
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'components'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'ComponentList'}[0]
          ->children('http://www.opengis.net/sensorML/1.0.1')->{'component'}
        as $component) {
          $niceName = sprintf("%s",$component->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'outputs'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'OutputList'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'output'}->attributes()->{'name'});
          $observedProperty = sprintf("%s",$component->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'outputs'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'OutputList'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'output'}[0]->children('http://www.opengis.net/swe/1.0.1')->{'Quantity'}[0]->attributes()->{'definition'});
          $uom = sprintf("%s",$component->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'outputs'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'OutputList'}[0]->children('http://www.opengis.net/sensorML/1.0.1')->{'output'}[0]->children('http://www.opengis.net/swe/1.0.1')->{'Quantity'}[0]->children('http://www.opengis.net/swe/1.0.1')->{'uom'}[0]->attributes()->{'code'});
          $swe2Providers[$provider]['sos']['procedures']['stations'][$k]['sensors'][$observedProperty] = array(
             'name' => $niceName
            ,'uom'  => $uom
          );
        }
      }
    }
  }

  // get the data
  foreach ($swe2Providers as $provider => $xmlLoc) {
    foreach (array_keys($swe2Providers[$provider]['sos']['procedures']['stations']) as $station) {
      if (array_pop(explode(':',$station)) != '45029') {
        // continue;
      }
      $loc = explode(' ',$swe2Providers[$provider]['sos']['procedures']['stations'][$station]['info']['location']);
      $siteId = array_pop(explode(':',$station));
      array_push($sites,array(
         'descr'        => $swe2Providers[$provider]['sos']['procedures']['stations'][$station]['info']['longName']
        ,'provider'     => $provider
        ,'organization' => $swe2Providers[$provider]['sos']['procedures']['stations'][$station]['info']['publisher']['name']
        ,'lon'          => $loc[1]
        ,'lat'          => $loc[0]
        ,'timeSeries'   => array()
        ,'topObs'       => array()
        ,'url'          => str_replace('___SITE___',$siteId,$swe2Providers[$provider]['provUrl'])
        ,'siteType'     => $swe2Providers[$provider]['sos']['procedures']['stations'][$station]['info']['platformType']
        ,'siteId'       => $siteId
      ));
      foreach (array_keys($swe2Providers[$provider]['sos']['procedures']['stations'][$station]['sensors']) as $sensor) {
        $u = $swe2Providers[$provider]['sos']['GetObservation']['href']
          .'&request=GetObservation'
          .'&service='.$swe2Providers[$provider]['sos']['service']
          .'&version='.$swe2Providers[$provider]['sos']['version']
          .'&responseFormat='.$swe2Providers[$provider]['sos']['GetObservation']['responseFormat']
          .'&offering='.$swe2Providers[$provider]['sos']['network']
          .'&procedure='.$station
          .'&observedProperty='.$sensor
          ."&eventTime=$dBegin/$dEnd";
        print $u."\n"; 
        $xml = @simplexml_load_file($u);
        $n = $swe2Providers[$provider]['sos']['procedures']['stations'][$station]['sensors'][$sensor]['name'];
        if (array_key_exists($n,$swe2Providers[$provider]['varMap'])) {
          $n = $swe2Providers[$provider]['varMap'][$n];
        }
        if ($xml !== FALSE && $xml->children('http://www.opengis.net/om/1.0')->{'member'} && $xml->children('http://www.opengis.net/om/1.0')->{'member'}[0]->children('http://www.opengis.net/om/1.0')) {
          $value = sprintf("%s",$xml
            ->children('http://www.opengis.net/om/1.0')->{'member'}[0]
            ->children('http://www.opengis.net/om/1.0')->{'Observation'}[0]
            ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
            ->children('http://www.opengis.net/swe/2.0')->{'DataStream'}[0]
            ->children('http://www.opengis.net/swe/2.0')->{'values'}
          );
          $i = count($sites) - 1;
          foreach (explode("\n",$value) as $line) {
            // 2012-11-03T14:50:00.000Z,sensor1,1026.4
            $p = explode(',',$line);
            $t = strtotime($p[0]);
            $a = convertUnits($p[2],$swe2Providers[$provider]['sos']['procedures']['stations'][$station]['sensors'][$sensor]['uom'],$tUom == 'english');
            if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
              $v = array(
                $a[0]['uom'] => array()
              );
              if (count($a) == 2) {
                $v[$a[1]['uom']] = array();
              }
              $sites[$i]['timeSeries'][$n] = array(
                 'v' => $v
                ,'t' => array()
              );
              $sites[$i]['topObs'][$n] = array(
                 'v' => array()
                ,'t' => 0
              );
            }
            array_push($sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
            if (count($a) == 2) {
              array_push($sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
            }
            array_push($sites[$i]['timeSeries'][$n]['t'],$t);
            if ($t >= $sites[$i]['topObs'][$n]['t']) {
              $sites[$i]['topObs'][$n]['t'] = $t;
              $sites[$i]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
              if (count($a) == 2) {
                $sites[$i]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
              }
            }
          }
        }
        // don't have any fresh data for this sensor so add a stub
        else if ($xml !== FALSE) {
          $i = count($sites) - 1;
          $sites[$i]['topObs'][$n]['t'] = false;
          $sites[$i]['topObs'][$n]['v'] = array();
          $a = convertUnits(0,$swe2Providers[$provider]['sos']['procedures']['stations'][$station]['sensors'][$sensor]['uom'],$tUom == 'english');
          $sites[$i]['topObs'][$n]['v'][$a[0]['uom']] = null;
          if (count($a) == 2) {
            $sites[$i]['topObs'][$n]['v'][$a[1]['uom']] = null;
          }
          if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
            $sites[$i]['timeSeries'][$n] = array();
            $v = array(
              $a[0]['uom'] => null
            );
            if (count($a) == 2) {
              $v[$a[1]['uom']] = null;
            }
            $sites[$i]['timeSeries'][$n] = array(
               'v' => $v
              ,'t' => false
            );
          }
        }
      }
    } 
  }

  foreach ($sosProviders as $provider => $xmlLoc) {
    print $sosProviders[$provider]['getCaps']."\n";
    $xml = @simplexml_load_file($sosProviders[$provider]['getCaps']);
    $describeSensor = '';
    $getObservation = '';
    foreach ($xml->children('http://www.opengis.net/ows/1.1')->{'OperationsMetadata'}[0]->children('http://www.opengis.net/ows/1.1')->{'Operation'} as $o) {
      if ($o->attributes()->{'name'} == 'DescribeSensor') {
        $describeSensor = sprintf("%s",$o
          ->children('http://www.opengis.net/ows/1.1')->{'DCP'}[0]
          ->children('http://www.opengis.net/ows/1.1')->{'HTTP'}[0]
          ->children('http://www.opengis.net/ows/1.1')->{'Get'}[0]
          ->attributes('http://www.w3.org/1999/xlink')->{'href'}
        );
      }
      else if ($o->attributes()->{'name'} == 'GetObservation') {
        $getObservation = sprintf("%s",$o
          ->children('http://www.opengis.net/ows/1.1')->{'DCP'}[0]
          ->children('http://www.opengis.net/ows/1.1')->{'HTTP'}[0]
          ->children('http://www.opengis.net/ows/1.1')->{'Get'}[0]
          ->attributes('http://www.w3.org/1999/xlink')->{'href'}
        );
      }
    }
    foreach ($xml->children('http://www.opengis.net/sos/1.0')->{'Contents'}[0]->children('http://www.opengis.net/sos/1.0')->{'ObservationOfferingList'}[0]->children('http://www.opengis.net/sos/1.0')->{'ObservationOffering'} as $o) {
      $chld   = $o->children('http://www.opengis.net/gml');
      $id     = str_replace('station-','',sprintf("%s",$o->attributes('http://www.opengis.net/gml')->{'id'}));
      $loc    = explode(' ',sprintf("%s",$chld->{'boundedBy'}[0]->{'Envelope'}[0]->{'lowerCorner'}));
      $dSensor = array();
      $getObs  = array();
      if (contains($bbox,$loc[1],$loc[0])) { // && $id == '45029') {
        foreach ($o->{'observedProperty'} as $prop) {
          $p = explode('/',$prop->attributes('http://www.w3.org/1999/xlink')->{'href'});
          if (array_key_exists(sprintf("%s",$p[count($p)-1]),$sosProviders[$provider]['variables'])) {
            array_push($dSensor,array(
               'name' => $sosProviders[$provider]['variables'][sprintf("%s",$p[count($p)-1])]
              ,'url'  => $describeSensor
                .'?request=DescribeSensor&service=SOS&version=1.0.0'
                .sprintf(
                   "&outputFormat=%s&procedure=%s"
                  ,'text/xml;subtype="sensorML/1.0.1"'
                  ,sprintf("%s",$o->{'procedure'}[0]->attributes('http://www.w3.org/1999/xlink')->{'href'})
                )
            ));
            array_push($getObs,array(
               'name' => sprintf("%s",$p[count($p)-1])
              ,'url'  => $getObservation
                .'?request=GetObservation&service=SOS&version=1.0.0'
                ."&eventTime=$dBegin/$dEnd"
                .sprintf(
                   "&responseFormat=%s&offering=%s&procedure=%s&observedProperty=%s"
                  ,'text/xml;schema="ioos/0.6.1"'
                  ,sprintf("%s",$o->{'procedure'}[0]->attributes('http://www.w3.org/1999/xlink')->{'href'})
                  ,sprintf("%s",$o->{'procedure'}[0]->attributes('http://www.w3.org/1999/xlink')->{'href'})
                  ,$p[count($p)-1]
                )
            ));
          }
        }
        // Check to see if there is already a hit for this id (e.g. a GLOS swe2 buoy w/ the same id).
        // If so, let swe2 win by skipping this one.
        $swe2Exists = false;
        for ($i = 0; $i < count($sites); $i++) {
/*
  For now, allow NDBC to win by commenting out the next line and applying the array_splice stuff below.
          $swe2Exists = $swe2Exists || (array_key_exists('siteId',$sites[$i]) && $sites[$i]['siteId'] == $id);
*/
          if (array_key_exists('siteId',$sites[$i]) && $sites[$i]['siteId'] == $id) {
            array_splice($sites,$i,1);
          }
        }
        if (!$swe2Exists) {
          array_push($sites,array(
             'descr'        => sprintf("%s%s",$id,$chld->{'description'} != 'GetCapabilities' ? ' - '.$chld->{'description'} : '')
            ,'provider'     => $provider
            ,'organization' => ''
            ,'lon'          => $loc[1]
            ,'lat'          => $loc[0]
            ,'dSensor'      => $dSensor
            ,'getObs'       => $getObs
            ,'timeSeries'   => array()
            ,'topObs'       => array()
            ,'url'          => str_replace('___SITE___',$id,$sosProviders[$provider]['provUrl'])
            ,'siteType'     => $sosProviders[$provider]['siteType']
          ));
        }
      }
    }
  }

  for ($i = 0; $i < count($sites); $i++) {
    if (!array_key_exists('getObs',$sites[$i])) {
      continue;
    }
    for ($j = 0; $j < count($sites[$i]['getObs']); $j++) {
      if ($sites[$i]['organization'] == '') {
        echo $sites[$i]['dSensor'][$j]['url']."\n";
        $xml = @simplexml_load_string(@file_get_contents($sites[$i]['dSensor'][$j]['url']));
        if ($xml && $xml->children('http://www.opengis.net/sensorML/1.0.1')->{'member'}) {
          $sites[$i]['organization'] = sprintf("%s",$xml
              ->children('http://www.opengis.net/sensorML/1.0.1')->{'member'}[0]
              ->children('http://www.opengis.net/sensorML/1.0.1')->{'System'}[0]
              ->children('http://www.opengis.net/sensorML/1.0.1')->{'contact'}[0]
              ->children('http://www.opengis.net/sensorML/1.0.1')->{'ResponsibleParty'}[0]
              ->children('http://www.opengis.net/sensorML/1.0.1')->{'organizationName'}
          );
        }
      }
      echo $sites[$i]['getObs'][$j]['url']."\n";
      // This is INSANE but simplexml_load_* seems to bomb if there isn't a COMMENT STRING before the 1st entry.  WTF?!  So always stick one in.
      $xml = @simplexml_load_string(preg_replace('/(<om:CompositeObservation)([^>]*>)/','${1}${2}'."\n  <!--===============================================-->",@file_get_contents($sites[$i]['getObs'][$j]['url'])));
      if ($xml && $xml->children('http://www.opengis.net/om/1.0')->{'result'}) {
        foreach ($xml
            ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Array'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'Composite'}
          as $pointComposite) {
          $t = strtotime(sprintf("%s",$pointComposite
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeContext'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'TimeInstant'}[0]
            ->children('http://www.opengis.net/gml/3.2')->{'timePosition'}[0]
          ));
          $v = '';
          if (isset($pointComposite
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeValue'})) {
            foreach ($pointComposite
              ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
              ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeValue'}[0]
              ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
              ->children('http://www.noaa.gov/ioos/0.6.1')->{'Quantity'}
            as $pointObservations) {
              $n = sprintf("%s",$pointObservations->attributes()->{'name'});
              if (sprintf("%s",$pointObservations) != '') {
                $a = convertUnits(sprintf("%s",$pointObservations),sprintf("%s",$pointObservations->attributes()->{'uom'}),$tUom == 'english');
                if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
                  $v = array(
                    $a[0]['uom'] => array()
                  );
                  if (count($a) == 2) {
                    $v[$a[1]['uom']] = array();
                  }
                  $sites[$i]['timeSeries'][$n] = array(
                     'v' => $v
                    ,'t' => array()
                  );
                  $sites[$i]['topObs'][$n] = array(
                     'v' => array()
                    ,'t' => 0
                  );
                }
                if (!is_array($sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']])) {
                  $sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']] = array();
                }
                array_push($sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
                if (count($a) == 2) {
                  if (!is_array($sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']])) {
                    $sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']] = array();
                  }
                  array_push($sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
                }
                if (!is_array($sites[$i]['timeSeries'][$n]['t'])) {
                  $sites[$i]['timeSeries'][$n]['t'] = array();
                }
                array_push($sites[$i]['timeSeries'][$n]['t'],$t);
                if ($t >= $sites[$i]['topObs'][$n]['t']) {
                  $sites[$i]['topObs'][$n]['t'] = $t;
                  $sites[$i]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
                  if (count($a) == 2) {
                    $sites[$i]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
                  }
                }
              }
            }
          }
          else if (isset($pointComposite
            ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
            ->children('http://www.noaa.gov/ioos/0.6.1')->{'ValueArray'}[0])) {
            // this is an ADCP w/ Depth, CurrentDirection, and CurrentSpeed
            $n = 'CurrentsVerticalProfile';
            $uom = '';
            foreach ($pointComposite
              ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
              ->children('http://www.noaa.gov/ioos/0.6.1')->{'ValueArray'}[0]
              ->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]
              ->children('http://www.noaa.gov/ioos/0.6.1')->{'CompositeValue'}
            as $bin) {
              $d = array();
              foreach ($bin->children('http://www.opengis.net/gml/3.2')->{'valueComponents'}[0]->children('http://www.noaa.gov/ioos/0.6.1') as $val) {
                // we only care about knots (in the case of uom == 'english'), not mph, and we know this is the 0th element
                $a = convertUnits(sprintf("%s",$val),sprintf("%s",$val->attributes()->{'uom'}),$tUom == 'english');
                $d[sprintf("%s",$val->attributes()->{'name'})] = $a[0];
              }
              // assume speed & dir always exist as a pair
              if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
                $sites[$i]['timeSeries'][$n] = array(
                   't' => array()
                  ,'v' => array(
                    $d['CurrentSpeed']['uom'] => array(
                      'Depth' => array(
                        $d['Depth']['uom'] => array()
                      )
                    )
                    ,$d['CurrentDirection']['uom'] => array(
                      'Depth' => array(
                        $d['Depth']['uom'] => array()
                      )
                    )
                  )
                );
              }
              $uom = $d['CurrentSpeed']['uom'];
              if (!array_key_exists($d['Depth']['val'],$sites[$i]['timeSeries'][$n]['v'][$d['CurrentSpeed']['uom']]['Depth'][$d['Depth']['uom']])) {
                $sites[$i]['timeSeries'][$n]['v'][$d['CurrentSpeed']['uom']]['Depth'][$d['Depth']['uom']][$d['Depth']['val']] = array();
                $sites[$i]['timeSeries'][$n]['v'][$d['CurrentDirection']['uom']]['Depth'][$d['Depth']['uom']][$d['Depth']['val']] = array();
              }
              array_push($sites[$i]['timeSeries'][$n]['v'][$d['CurrentSpeed']['uom']]['Depth'][$d['Depth']['uom']][$d['Depth']['val']],$d['CurrentSpeed']['val']);
              array_push($sites[$i]['timeSeries'][$n]['v'][$d['CurrentDirection']['uom']]['Depth'][$d['Depth']['uom']][$d['Depth']['val']],$d['CurrentDirection']['val']);
            }
            array_push($sites[$i]['timeSeries'][$n]['t'],$t);
            if ($t >= $sites[$i]['topObs'][$n]['t']) {
              $sites[$i]['topObs'][$n]['t'] = $t;
              $sites[$i]['topObs'][$n]['v'] = array(
                $uom => null 
              );
            }
          }
        }
      }
      // don't have any fresh data for this sensor so add a stub
      else if (
        $xml 
        && $xml->children('http://www.opengis.net/ows/1.1')->{'Exception'} 
        && (sprintf("%s",$xml->children('http://www.opengis.net/ows/1.1')->{'Exception'}[0]->attributes()->{'exceptionCode'}) == 'NoApplicableCode')
      ) {
        $pseudoName = $sites[$i]['dSensor'][$j]['name'];
        $sites[$i]['topObs'][$pseudoName]['t'] = false;
        $sites[$i]['topObs'][$pseudoName]['v'] = array();
        if (!array_key_exists($pseudoName,$sites[$i]['timeSeries'])) {
          $sites[$i]['timeSeries'][$pseudoName] = array();
        }
      }
    }
  }

  function getUSGS($wmlProviders,$provider,$counties,$dBegin,$dEnd,$tUom,&$sites) {
    $urls = array();
    // can only get 10 counties at a time max
    for ($i = 0; $i < count($counties); $i += 10) {
      array_push(
         $urls
        ,sprintf("http://waterservices.usgs.gov/nwis/iv/?format=waterml,1.1&startDT=%s&endDt=%s&countyCd=%s"
          ,$dBegin
          ,$dEnd
          ,implode(array_slice($counties,$i,10),',')
        )
      );
    }

    $sitesCache = array();
    for ($i = 0; $i < count($urls); $i++) {
      echo $urls[$i]."\n";
      $xml = @simplexml_load_file($urls[$i]);
      if ($xml == FALSE) {
        echo "\tERROR PARSING\n";
        continue;
      }
      foreach ($xml->children('http://www.cuahsi.org/waterML/1.1/')->{'timeSeries'} as $ts) {
        $id    =  sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'sourceInfo'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'siteCode'}
        );
        $descr = sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'sourceInfo'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'siteName'}
        );
        $lon   = sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'sourceInfo'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'geoLocation'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'geogLocation'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'longitude'}
        );
        $lat   = sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'sourceInfo'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'geoLocation'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'geogLocation'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'latitude'}
        );
        $n      = sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'variable'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'variableDescription'}
        );
        if (array_key_exists($n,$wmlProviders[$provider]['varMap'])) {
          $n = $wmlProviders[$provider]['varMap'][$n];
        }
        else {
          continue;
        }
        $u      = array_pop(explode(',',sprintf("%s"
          ,$ts
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'variable'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'unit'}[0]
            ->children('http://www.cuahsi.org/waterML/1.1/')
            ->{'unitCode'}
        )));
        if ($u == 'deg F') {
          $u = 'F';
        }

        if (!array_key_exists($id,$sitesCache)) {
          $sitesCache[$id] = array(
             'descr'        => $descr
            ,'provider'     => $provider
            ,'organization' => ''
            ,'lon'          => $lon
            ,'lat'          => $lat
            ,'timeSeries'   => array()
            ,'topObs'       => array()
            ,'url'          => str_replace('___SITE___',$id,$wmlProviders[$provider]['provUrl'])
            ,'siteType'     => $wmlProviders[$provider]['siteType']
          );
        }
        foreach ($ts->children('http://www.cuahsi.org/waterML/1.1/')->{'values'}[0]->children('http://www.cuahsi.org/waterML/1.1/')->{'value'} as $val) {
          $a = convertUnits(sprintf("%s",$val),$u,$tUom == 'english');
          if (!array_key_exists($n,$sitesCache[$id]['timeSeries'])) {
            $v = array(
              $a[0]['uom'] => array()
            );
            if (count($a) == 2) {
              $v[$a[1]['uom']] = array();
            }
            $sitesCache[$id]['timeSeries'][$n] = array(
               'v' => $v
              ,'t' => array()
            );
            $sitesCache[$id]['topObs'][$n] = array(
               'v' => array()
              ,'t' => 0
            );
          }
          array_push($sitesCache[$id]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
          if (count($a) == 2) {
            array_push($sitesCache[$id]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
          }
          $t = strtotime(sprintf("%s",$val->attributes()->{'dateTime'}));
          array_push($sitesCache[$id]['timeSeries'][$n]['t'],$t);
          if ($t >= $sitesCache[$id]['topObs'][$n]['t']) {
            $sitesCache[$id]['topObs'][$n]['t'] = $t;
            $sitesCache[$id]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
            if (count($a) == 2) {
              $sitesCache[$id]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
            }
          }
        }
      }
    }
    foreach ($sitesCache as $s) {
      array_push($sites,$s);
    }
  }

  function getNWS($metarProviders,$provider,$bbox,$hours,$tUom,&$sites) {
    $metar2id = array();
    foreach (explode("\n",file_get_contents('http://weather.noaa.gov/data/nsd_cccc.txt')) as $l) {
      $p = explode(';',$l);
      if (count($p) >= 4) {
        $metar2id[$p[0]] = $p[3];
      }
    }
    $sitesCache = array();
    $url = sprintf(
       "http://www.aviationweather.gov/adds/dataserver_current/httpparam?dataSource=metars&requestType=retrieve&format=xml&minLat=%f&minLon=%f&maxLat=%f&maxLon=%f&hoursBeforeNow=48"
      ,$bbox[1]
      ,$bbox[0]
      ,$bbox[3]
      ,$bbox[2]
    );
    echo "$url\n";
    $xml = simplexml_load_file($url);
    foreach ($xml->{'data'}[0]->{'METAR'} as $metar) {
      $id    = array_key_exists(sprintf("%s",$metar->{'station_id'}),$metar2id) ? $metar2id[sprintf("%s",$metar->{'station_id'})] : sprintf("%s",$metar->{'station_id'});
      $descr = $id;
      $lon   = sprintf("%s",$metar->{'longitude'});
      $lat   = sprintf("%s",$metar->{'latitude'});
      $t     = strtotime(sprintf("%s",$metar->{'observation_time'}));
      $txt   = sprintf("%s",$metar->{'raw_text'});
      if (!array_key_exists($id,$sitesCache)) {
        $sitesCache[$id] = array(
           'descr'        => $descr
          ,'provider'     => $provider
          ,'organization' => ''
          ,'lon'          => $lon
          ,'lat'          => $lat
          ,'timeSeries'   => array()
          ,'topObs'       => array()
          ,'url'          => str_replace('___SITE___',$metar->{'station_id'},$metarProviders[$provider]['provUrl'])
          ,'siteType'     => $metarProviders[$provider]['siteType']
        );
      }
      foreach ($metar->children() as $node) {
        if (array_key_exists($node->getName(),$metarProviders[$provider]['varMap'])) {
          $n   = $node->getName();
          $u   = array_pop(explode('_',$n));
          $val = sprintf("%s",$node);
          if ($u == 'kt') {
            $u   = 'm/s';
            $val = sprintf("%s",$val * 0.514);
          }
          else if ($u == 'degrees') {
            $u = 'deg';
          }
          else if ($u == 'c') {
            $u = 'C';
          }
          $a   = convertUnits($val,$u,$tUom == 'english');
          $n   = $metarProviders[$provider]['varMap'][$n];
          if (!array_key_exists($n,$sitesCache[$id]['timeSeries'])) {
            $k = $a[0]['uom'];
            $v = array(
              $k => array()
            );
            if (count($a) == 2) {
              $v[$a[1]['uom']] = array();
            }
            $sitesCache[$id]['timeSeries'][$n] = array(
               'v' => $v
              ,'t' => array()
            );
            $sitesCache[$id]['topObs'][$n] = array(
               'v' => array()
              ,'t' => 0
            );
          }
          array_push($sitesCache[$id]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
          if (count($a) == 2) {
            array_push($sitesCache[$id]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
          }
          array_push($sitesCache[$id]['timeSeries'][$n]['t'],$t);
          if ($t >= $sitesCache[$id]['topObs'][$n]['t']) {
            $sitesCache[$id]['topObs'][$n]['t'] = $t;
            $sitesCache[$id]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
            if (count($a) == 2) {
              $sitesCache[$id]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
            }
          }
        }
      }
    }
    foreach ($sitesCache as $s) {
      array_push($sites,$s);
    }
  }

  function getNDBCText($ndbcTextProviders,$provider,$dBegin,$tUom,&$sites) {
    $t0 = strtotime($dBegin);
    // hit the RSS to grab position info
    foreach ($ndbcTextProviders[$provider]['stations'] as $s) {
      $lon   = '';
      $lat   = ''; 
      $title = '';
      $xml = @simplexml_load_file("http://www.ndbc.noaa.gov/data/latest_obs/$s.rss");
      if ($xml) {
        $title = sprintf(
          "%s"
          ,$xml
            ->children()->{'channel'}[0]
            ->children()->{'title'}
        );
        $title = str_replace('NDBC - Station ','',$title);
        list($lat,$lon) = explode(' ',sprintf(
          "%s"
          ,$xml
            ->children()->{'channel'}[0]
            ->children()->{'item'}[0]
            ->children('http://www.georss.org/georss')->{'point'}
        ));

        array_push($sites,array(
           'descr'        => $title
          ,'provider'     => $provider
          ,'organization' => ''
          ,'lon'          => $lon
          ,'lat'          => $lat
          ,'timeSeries'   => array()
          ,'topObs'       => array()
          ,'url'          => str_replace('___SITE___',$s,$ndbcTextProviders[$provider]['provUrl'])
          ,'siteType'     => $ndbcTextProviders[$provider]['siteType']
        ));

        $i = count($sites) - 1;
        $csv = csv_to_array(file_get_contents("http://www.ndbc.noaa.gov/data/realtime2/$s.txt"),"/ +/");
        for ($j = 0; $j < count($csv); $j++) {
          $t = mktime((integer)$csv[$j]['hh'],(integer)$csv[$j]['mm'],0,(integer)$csv[$j]['MM'],(integer)$csv[$j]['DD'],(integer)$csv[$j]['#YY']);
          if ($t >= $t0) {
            foreach (array_keys($csv[0]) as $k) {
              if (!preg_match("/#YY|MM|DD|hh|mm/",$k)) {
                $uom = $csv[0][$k];
                if ($csv[$j][$k] == 'MM') {
                  continue;
                }
                $a = convertUnits($csv[$j][$k],$uom,$tUom == 'english');
                $n = $k;
                if (array_key_exists($n,$ndbcTextProviders[$provider]['varMap'])) {
                  $n = $ndbcTextProviders[$provider]['varMap'][$n];
                }
                if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
                  $v = array(
                    $a[0]['uom'] => array()
                  );
                  if (count($a) == 2) {
                    $v[$a[1]['uom']] = array();
                  }
                  $sites[$i]['timeSeries'][$n] = array(
                     'v' => $v
                    ,'t' => array()
                  );
                  $sites[$i]['topObs'][$n] = array(
                     'v' => array()
                    ,'t' => 0
                  );
                }
                array_push($sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
                if (count($a) == 2) {
                  array_push($sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
                }
                array_push($sites[$i]['timeSeries'][$n]['t'],$t);
                if ($t >= $sites[$i]['topObs'][$n]['t']) {
                  $sites[$i]['topObs'][$n]['t'] = $t;
                  $sites[$i]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
                  if (count($a) == 2) {
                    $sites[$i]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  function getSoap($soapProviders,$provider,$bbox,$dBegin,$dEnd,$tUom,&$sites) {
    require_once('nusoap/lib/nusoap.php');
    nusoap_base::setGlobalDebugLevel(0);
    $u      = substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/'));
    $wsdl   = new nusoap_client('http://cdmo.baruch.sc.edu/webservices2/requests.cfc?wsdl');
    $result = $wsdl->call('exportStationCodesXMLNew');

    $stations = array();
    foreach ($result['returnData']['data'] as $d) {
      // lump things by lon,lat
      $k = $d['Longitude'].','.$d['Latitude'];
      if ($d['Real_Time'] == 'R' && contains($bbox,-$d['Longitude'],$d['Latitude'])) {
        if (!array_key_exists($k,$stations)) {
          $stations[$k] = array($d);
        }
        else {
          array_push($stations[$k],$d);
        }
      }
    }

    $t0 = strtotime($dBegin);
    $d0 = date('m/d/Y',$t0);
    $d1 = date('m/d/Y',strtotime($dEnd));
    foreach ($stations as $k => $stats) {
      $descr = array();
      for ($i = 0; $i < count($stats); $i++) {
        array_push($descr,$stats[$i]['Station_Name']);
      }
      array_push($sites,array(
         'descr'        => implode(' & ',$descr)
        ,'provider'     => $provider
        ,'organization' => ''
        ,'lon'          => -$stats[0]['Longitude']
        ,'lat'          => $stats[0]['Latitude']
        ,'timeSeries'   => array()
        ,'topObs'       => array()
        ,'url'          => str_replace('___SITE___',$stats[0]['Station_Code'],$soapProviders[$provider]['provUrl'])
        ,'siteType'     => $soapProviders[$provider]['siteType']
      ));
      $i = count($sites) - 1;
      for ($j = 0; $j < count($stats); $j++) {
        $result = $wsdl->call('exportAllParamsDateRangeXMLNew',array(
           'tbl'       => $stats[$j]['Station_Code']
          ,'mindate'   => $d0
          ,'maxdate'   => $d1
          ,'fieldlist' => '*'
        ));
        if (is_array($result['returnData'])) {
          foreach ($result['returnData']['data'] as $d) {
            foreach ($d as $k => $val) {
              if ($val == '') {
                continue;
              }
              if (array_key_exists($k,$soapProviders[$provider]['varMap'])) {
                $uom = $soapProviders[$provider]['varMap'][$k]['uom'];
                $n   = $soapProviders[$provider]['varMap'][$k]['name'];
                $t   = strtotime($d['DateTimeStamp'].' UTC');
                if ($t < $t0) {
                  continue;
                }
                $a   = convertUnits($val,$uom,$tUom == 'english');
                if (!array_key_exists($n,$sites[$i]['timeSeries'])) {
                  $v = array(
                    $a[0]['uom'] => array()
                  );
                  if (count($a) == 2) {
                    $v[$a[1]['uom']] = array();
                  }
                  $sites[$i]['timeSeries'][$n] = array(
                     'v' => $v
                    ,'t' => array()
                  );
                  $sites[$i]['topObs'][$n] = array(
                     'v' => array()
                    ,'t' => 0
                  );
                }
                array_push($sites[$i]['timeSeries'][$n]['v'][$a[0]['uom']],$a[0]['val']);
                if (count($a) == 2) {
                  array_push($sites[$i]['timeSeries'][$n]['v'][$a[1]['uom']],$a[1]['val']);
                }
                array_push($sites[$i]['timeSeries'][$n]['t'],$t);
                if ($t >= $sites[$i]['topObs'][$n]['t']) {
                  $sites[$i]['topObs'][$n]['t'] = $t;
                  $sites[$i]['topObs'][$n]['v'][$a[0]['uom']] = $a[0]['val'];
                  if (count($a) == 2) {
                    $sites[$i]['topObs'][$n]['v'][$a[1]['uom']] = $a[1]['val'];
                  }
                }
              }
            }
          }
        }
        // insert a stub for anything that didn't make it
        foreach (explode(',',$stats[$j]['Params_Reported']) as $p) {
          if (array_key_exists($p,$soapProviders[$provider]['varMap'])) {
            $n = $soapProviders[$provider]['varMap'][$p]['name'];
            if (!array_key_exists($n,$sites[$i]['topObs'])) {
              $sites[$i]['topObs'][$n]['t'] = false;
              $sites[$i]['topObs'][$n]['v'] = array();
              $sites[$i]['timeSeries'][$n] = array();
            }
          }
        }
      }
    }
  }

  echo "finished fetching data\nassembling...\n";

  $features = array();
  for ($i = 0; $i < count($sites); $i++) {
    array_push($features,array(
       'type'     => 'Feature'
      ,'geometry' => array(
         'type'        => 'Point'
        ,'coordinates' => array(
           $sites[$i]['lon']
          ,$sites[$i]['lat']
        )
      )
      ,'properties'  => array(
         'descr'      => $sites[$i]['descr']
        ,'lon'        => $sites[$i]['lon']
        ,'lat'        => $sites[$i]['lat']
        ,'timeSeries' => !empty($sites[$i]['timeSeries']) ? $sites[$i]['timeSeries'] : null
        ,'topObs'     => !empty($sites[$i]['topObs']) ? $sites[$i]['topObs'] : null
        ,'url'        => $sites[$i]['url']
        ,'siteType'   => $sites[$i]['siteType']
        ,'provider'   => $sites[$i]['organization'] != '' ? $sites[$i]['organization'] : $sites[$i]['provider']
      )
    ));
  }

  echo "assembling done\nfiniding out what to name the json...\n";

  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  $id = 0;
  $result = pg_query("select nextval('json_seq_seq')");
  while ($line = pg_fetch_array($result)) {
    $id = $line[0];
  }

  echo "writing to json/obs.$id.json\n";

  $handle = fopen("json/obs.$id.json",'w');
  fwrite($handle,"[\n");
  for ($i = 0; $i < count($features); $i++) {
    fwrite($handle,json_encode($features[$i]));
    if ($i < count($features) - 1) {
      fwrite($handle,",\n");
    }
  }
  fwrite($handle,"\n]");
  fclose($handle);

  echo "json written\nupdating db...\n";

  pg_query($dbconn,'insert into json (f) values(\''."json/obs.$id.json".'\')');
  pg_close($dbconn);

  echo "all done!\n";

  // from http://www.php.net/manual/en/function.str-getcsv.php#104558
  function csv_to_array($input,$delimiter=',') {
    $header  = null;
    $data    = array();
    $csvData = str_getcsv($input,"\n");
    foreach ($csvData as $csvLine) {
      if (is_null($header)) {
        $header = preg_split($delimiter, $csvLine);
      }
      else {
        $items = preg_split($delimiter, $csvLine);
        for ($n = 0,$m = count($header); $n < $m; $n++) {
          $prepareData[$header[$n]] = $items[$n];
        }
        $data[] = $prepareData;
      }
    }
    return $data;
  }
?>
