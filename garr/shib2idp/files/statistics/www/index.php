<!DOCTYPE html>
<html lang="it">
<head>
        <meta charset="utf-8" />
        <title>Statistiche di utilizzo</title>
        <link href="reset.css" media="screen" rel="stylesheet" />
        <link href="stile.css" media="screen" rel="stylesheet" />
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
</head>
<?php
        $dati = $_GET['dati'];
        if ($dati == "") $dati = "sp";

        $year = $_GET['year'];
        $month = $_GET['month'];
        if ($year == "") $year = date('Y');
        if ($month == "") $month = date('m');

        $months = array('', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre');

        function addSubmitForm($dati,$months){
                echo "<p>Eseguire una ricerca per visualizzare le statistiche:</p>\n";
                echo "<form id=\"myform\" name=\"myform\" method=\"get\">\n";

                $datiDecoded = utf8_decode(urldecode($dati));

                echo "  <input type='hidden' name='dati' value='$datiDecoded' />";
                echo "  <select id='month' name='month'>;\n";
                for ($i = 1; $i <= 12; $i++){
                        $mon = ($i < 10) ? '0'.$i : $i;
                        echo "          <option id='". $months[$i] ."' value='".$mon."'";
                        if ($i == date("n")) echo " selected='selected'";
                        echo '>'.$months[$i].'</option>';
                }
                echo "  </select>";

                echo "  <select id='year' name='year'>";
                for ($i = 2012; $i <= date('Y'); $i++) {
                        echo "<option id='".$i."' value='".$i."'";
                        if ($i == date('Y')) echo " selected='selected'";
                        echo ">" . $i . "</option>";
                }
                echo "  </select>";
                echo "  <input type=\"submit\" value=\"Submit\" />";
                echo "</form>";
        }

?>
<body>
        <div id="menu">
                <div id="logo"><img src="shibboleth.png"/></div>
                <ul>
                        <li><a href="index.php?dati=sp" <?php if ($dati == "sp") echo "class=\"current\""; ?>>Dati per SP</a></li>
                        <li><a href="index.php?dati=user" <?php if ($dati == "user") echo "class=\"current\""; ?>>Dati per utente</a></li>
                        <li><a href="index.php?dati=questionario" <?php if ($dati == "questionario") echo "class=\"current\""; ?>>Dati per questionario IDEM</a></li>
                </ul>
        </div>

        <div id="corpo">
                <h1>Statistiche di utilizzo dell'IdP</h1>
                <h2>Identity provider installato nell'ambito del progetto GARR IdP-in-the-Cloud</h2>

                <?php
                include_once("db.php");

                if (!mysql_connect($sbhost, $dbuser, $dbpasswd))
                        die("Impossibile connettersi al database");
                if (!mysql_select_db($dbname))
                        die("Impossibile selezionare il database");
                $sps_names = array();
                $result = mysql_query("SELECT sp, name FROM sps");
                while($row = mysql_fetch_row($result)) {
                        $sps_names[$row[0]] = $row[1];
                }
                mysql_free_result($result);
                ?>

                <?php
                if ($dati == "sp") {
                        addSubmitForm($dati,$months);

                        $query1 = "SELECT DISTINCT sp FROM logins WHERE data LIKE '$year-$month-%';";
                        $query2 = "SELECT data, sp, SUM(logins) from logins WHERE data LIKE '$year-$month-%' GROUP BY data, sp";
                        $title = "Numero di login per data e per SP";
                        $titlepie = "\"Login a:<br/> \" + item + \"<br/> in data:<br/> \" + data";
                        $graph = True;

                } elseif ($dati == "user") {
                        addSubmitForm($dati, $months);

                        $query1 = "SELECT DISTINCT user FROM logins WHERE data LIKE '$year-$month-%';";
                        $query2 = "SELECT data, user, SUM(logins) from logins WHERE data LIKE '$year-$month-%' GROUP BY data, user";
                        $title = "Numero di login per data e per utente";
                        $titlepie = "\"Login di:<br/> \" + item + \"<br/> in data:<br/> \" + data";
                        $graph = True;

                } elseif ($dati == "questionario") {
                        addSubmitForm($dati, $months);

                        $result = mysql_query("SELECT SUM(logins) from logins WHERE YEAR(data) = ".$year." AND MONTH(data) = ".$month);
                        $row = mysql_fetch_row($result);
                        $totnum = $row[0];
                        mysql_free_result($result);
                ?>
                        <br/>
                        <h2>Il numero totale di login nel <?php echo $months[intval($month)]; ?> <?php echo intval($year); ?> &egrave;:
                        <?php
                                if ($month > date('n') && $year == date('Y')){
                                } else {
                                        echo "$totnum";
                                }
                        ?>
                        </h2>
                        <br/>

                <?php
                        $query1 = "SELECT \"Logins\" as ID FROM dual";
                        $query2 = "SELECT sp, SUM(logins) from logins WHERE YEAR(data) = ".$year." AND MONTH(DATA) = " . $month . " GROUP BY sp";
                        $title = "Numero di login per mese e per SP";
                        $titlepie = "";
                        $graph = False;
                }

                $result = mysql_query($query1);
                $i = 0;
                $items = array();
                while($row = mysql_fetch_row($result)) {
                        $curitem = $row[0];
                        if ($dati == "sp" and array_key_exists($curitem, $sps_names)) {
                                $curitem = $sps_names[$curitem];
                        }
                        $items[] = $curitem;
                }
                mysql_free_result($result);

                if ($graph) {
                        if ($month > date('n') && $year == date('Y')){
                                echo "<p style=\"font-weight: bold;color: red;\">DATA NON VALIDA!</p>";
                        }
                        else {
                ?>
                        <div id="graphs">
                        <br/>
                                <h2> Statistiche di <?php echo $months[intval($month)]; ?> <?php echo intval($year); ?> sull'utilizzo dell'IdP raggruppate per data e per
                                <?php
                                        if($dati == 'sp') echo "service provider";
                                        elseif ($dati == 'user') echo "utente";
                                ?>:</h2>

                                <div id="timeline"></div>
                                <div id="pie">
                                        <div id="pietitle"></div>
                                        <div id="piechart"></div>
                                </div>
                        </div>
                <?php
                        }
                }
                ?>
                <?php
                        if ($month > date('n') && $year == date('Y')){
                                echo "<p style=\"font-weight: bold;color: red; font-size: 20px;\">DATA NON VALIDA!</p>";
                        }
                        else {
                ?>
                <div id="table"></div>
                <?php
                }
                ?>
                </div>
                <script type="text/javascript">
                var arrdate = new Array();
                var dataItems = {};
                var viewItems = {};
                <?php
                foreach ($items as $itemname) {
                        echo "dataItems['".$itemname."'] = {};\n";
                        echo "viewItems['".$itemname."'] = true;\n";
                }

                $datatable = array();
                $result = mysql_query($query2);
                $fields_num = mysql_num_fields($result);
                while($row = mysql_fetch_row($result)) {
                        if ($dati == "questionario") {
                                $datatable[$row[0]] = "". $row[1];
                        } else {
                                $curitem = $row[1];
                                if ($dati == "sp" and array_key_exists($curitem, $sps_names)) {
                                        $curitem = $sps_names[$curitem];
                                }
                                $datatable[$row[0]][$curitem] = $row[2];
                        }
                }
                mysql_free_result($result);
                foreach ($datatable as $data => $itemtable) {
                        echo "arrdate.push('".$data."');\n";
                        if ($dati == "questionario") {
                                echo "dataItems['Logins']['".$data."'] = ".$itemtable.";\n";
                        } else {
                                foreach ($items as $itemname) {
                                        $logins = $itemtable[$itemname];
                                        if ($logins == "") $logins = "0";
                                        echo "dataItems['".$itemname."']['".$data."'] = ".$logins.";\n";
                                }
                        }
                }
                ?>
                function drawChart() {
                var data = new google.visualization.DataTable();
                var dataAll = new google.visualization.DataTable();
                <?php
                        if ($dati == "questionario") {
                                echo "data.addColumn('string', 'Service Provider');";
                                echo "dataAll.addColumn('string', 'Service Provider');";
                        } else {
                                echo "data.addColumn('string', 'Data');";
                                echo "dataAll.addColumn('string', 'Data');";
                        }
                        foreach ($items as $itemname) {
                                echo "data.addColumn('number', '".$itemname."');\n";
                                echo "dataAll.addColumn('number', '".$itemname."');\n";
                        }
                ?>
                for (var i in arrdate) {
                        var rowdata = new Array();
                        var rowdataAll = new Array();
                        rowdata.push(arrdate[i]);
                        rowdataAll.push(arrdate[i]);
                        for (var curitem in dataItems) {
                                rowdataAll.push(dataItems[curitem][arrdate[i]]);
                                if (viewItems[curitem] == true) {
                                        rowdata.push(dataItems[curitem][arrdate[i]]);
                                } else {
                                        rowdata.push(0);
                                }
                        }
                        data.addRow(rowdata);
                        dataAll.addRow(rowdataAll);
                }
                <?php
                if ($graph) {
                ?>
                        var optionsC1 = {
                        title: '<?= $title ?>',
                        vAxis: {title: 'Logins', textStyle: {fontSize: 11}},
                        hAxis: {textStyle: {fontSize: 11}},
                                legend: {position: 'bottom', textStyle: {fontSize: 13}},
                        chartArea: {left:60, bottom:30, top:20, right:20, width:"100%"},
                        isStacked: true,
                           series: {}
                        };
                        var count = 0;
                        for (var curitem in viewItems) {
                                if (!viewItems[curitem]) {
                                        optionsC1['series'][count] = {};
                                        optionsC1['series'][count]['color'] = 'white';
                                        optionsC1['series'][count]['areaOpacity'] = 0;
                                        optionsC1['series'][count]['lineWidth'] = 0;
                                        optionsC1['series'][count]['visibleInLegend'] = true;
                                }
                                count++;
                        }
                        var chart = new google.visualization.SteppedAreaChart(document.getElementById('timeline'));
                        chart.draw(data, optionsC1);
                <?php
                }
                ?>
                var optionsT = {
                        showRowNumber: false,
                        page: 'enable',
                        pageSize: 14,
                        pagingSymbols: {prev: 'Dati precedenti', next: 'Dati successivi'},
                        pagingButtonsConfiguration: 'auto',
                        sortColumn: 0,
                        sortAscending: false,
                };
                var table = new google.visualization.Table(document.getElementById('table'));
                table.draw(dataAll, optionsT);
                <?php
                if ($graph) {
                ?>
                        google.visualization.events.addListener(chart, 'select', function() {
                                var selection = chart.getSelection();
                                var selecteddata = null;
                                var selecteditem = null;
                                $('#pie').hide();
                                for (var i = 0; i < selection.length; i++) {
                                        var item = selection[i];
                                        if (item.row != null && item.column != null) {
                                                selecteddata = data.getValue(item.row, 0);
                                                selecteditem = data.getColumnLabel(item.column);
                                        } else if (item.row != null) {
                                                selecteddata = data.getValue(item.row, 0);
                                        } else if (item.column != null) {
                                                selecteditem = data.getColumnLabel(item.column);
                                        }
                                }
                var newdata = new google.visualization.DataTable();
                <?php
                if ($dati == "questionario") {
                        echo "data.addColumn('string', 'Service Provider');";
                } else {
                        echo "data.addColumn('string', 'Data');";
                }
                foreach ($sps as $spname) echo "data.addColumn('number', '".$spname."');\n";
                ?>
                if (selecteddata == null) {
                        viewItems[selecteditem] = !viewItems[selecteditem];
                        drawChart();
                }
                else {
                        drawPie(selecteddata, selecteditem);
                }
                });
                <?php
                }
                ?>
                        $('text').each(function () {
                                var items = new Array();
                                <?php foreach ($items as $itemname) echo "items.push('".$itemname."');\n"; ?>
                                for (var i = 0; i < items.length; i++) {
                                        var testo = $(this).text().replace("...", "");
                                        if (items[i].indexOf(testo) == 0) {
                                                $(this).attr("id", "legend");
                                        }
                                }
                        });
                }
                <?php
                if ($graph) {
                ?>
                        function drawPie(data, item) {
                                $.ajax({
                                        type: "GET",
                                        url: "detailed.php",
                                        data: {
                                                dati: "<?= $dati ?>",
                                                item: item,
                                                data: data
                                        },
                                        dataType: "json",
                                }).done(function(jsonData) {
                                        $('#pie').show();
                                        $('#pietitle').html(<?= $titlepie ?>);

                                        var chartdata = new google.visualization.DataTable();
                                        chartdata.addColumn('string', 'Item2');
                                        chartdata.addColumn('number', 'Login');
                                        for (var i = 0; i < jsonData['values'].length; i++) {
                                                chartdata.addRow(jsonData['values'][i]);
                                        }
                                        var options = {
                                                legend: {position: 'top', maxLines: 5, textStyle: {fontSize: 10}},
                                        };
                                        new google.visualization.PieChart(document.getElementById('piechart')).draw(chartdata, options);
                                });
                        }
                <?php
                }
                ?>
                google.load('visualization', '1', {'packages':['corechart','table']});
                google.setOnLoadCallback(drawChart);
                </script>
        </div>
</body>
</html>
