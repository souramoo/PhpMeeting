<?php
$file = $_GET['file'];
$file = "polls/1";

$notif = "";
$notiftype = "info";

session_start();

if(isset($_POST['data'])) {
    $handle = fopen($file, "r");
    if ($handle) {
        // if accepting a value, read no of options, then read 0 to n from datastream
        $title = fgetss($handle);
        $author = fgetss($handle);
        $location = fgetss($handle);
        $created = fgetss($handle) + 0;
        $expires = fgetss($handle) + 0;
        $init = fgets($handle);
        fclose($handle);
        if($expires > 0 && time() > $expires) {
            $notif = "<b>Sorry!</b> This poll has expired, we can no longer update it.";
            $notiftype = "info";
        } else {
            $cnt = count(explode("|", $init));

            $data = json_decode($_POST['data'], true);

            $newline = base64_encode(strip_tags(trim(preg_replace('/\s\s+/', ' ', $data["name"]))));
            for($i=0;  $i < $cnt; $i++){
                $val = 0;
                if($data[$i] == 1) $val = 1;
                if($data[$i] == 2) $val = 2;
                $newline .= "|".$val;
            }

            $handle = fopen($file, "a");

            if ($handle) {
                fwrite($handle, $newline . "\n");
                fclose($handle);
                $notif = "<b>Thanks!</b> Your submission has been saved";
                $notiftype = "success";
            } else {
                $notif = "<b>Error</b> Writing to file. Please let the admins know!";
                $notiftype = "danger";
            }
        }
    } else {
        $notif = "<b>Error</b> Reading file. Please let the admins know!";
        $notiftype = "danger";
    }
$_SESSION["msg"] = $notif;
$_SESSION["msgtype"] = $notiftype;
die($notif);
}

$handle = fopen($file, "r");
$table = "";
$title = "";
if ($handle) {
    $title = base64_decode(fgetss($handle));
    $author = base64_decode(fgetss($handle));
    $location = base64_decode(fgetss($handle));
    $created = fgetss($handle) + 0;
    $expires = fgetss($handle) + 0;
    $init = fgets($handle);

    $table .= "<h1>" . $title . "</h1>";
    $table .= "<i>By " . $author . "</i><br />";
    if(strcmp("", trim($location)) != 0) $table .= "<i>Location: " . $location . "</i><br />";
    $table .= "<i>Created on " . date('d/m/Y', $created) . ($expires == 0 ? "" : ", expires on " . date('d/m/Y', $expires)) . "</i><br /><br />";

    $table .= '<table class="table table-hover table-bordered"><thead class="thead-inverse"><tr><th style="width:20%">Name</th>';
    $totals = array();
    $options = explode("|", $init);
    foreach($options as $date) {
        $table .= "<th>" . base64_decode($date) . "</th>";
        $totals[] = array(0, 0);
    }
    $table .= "</tr></thead><tbody>";
    // Show current entries
    while (($line = fgets($handle)) !== false) {
        $stats = explode("|", $line);
        $table .= "<tr><td><p>" . base64_decode($stats[0]) . "</p></td>";
        array_shift($stats);
        for($i = 0; $i < count($stats); $i++) {
            $mode = $stats[$i] + 0;
            if($mode == 1) {
                $table .= "<td class=\"success\">&nbsp;</td>";
                $totals[$i][0]++;
            } else if($mode == 2) {
                $table .= "<td class=\"warning\">&nbsp;</td>";
                $totals[$i][1]++;
            } else {
                $table .= "<td class=\"danger\">&nbsp;</td>";
            }
        }
        $table .= "</tr>";
    }
    // add in a new entry
    $table .= "<tr><td><input id=\"name\" class=\"form-control\" placeholder=\"Your name here...\" /></td>";
    for($i = 0; $i < count($options); $i++) {
        $table .= "<td style=\"text-align:center;\" data-option=\"".$i."\">" . "<a class=\"ynm\" href=\"#\">Yes</a><br /><a class=\"ynm\" href=\"#\">(Yes)</a><br /><a class=\"ynm\" href=\"#\">No</a>" . "</td>";
    }
    $table .= "</tr>";

    // Show totals so far
    $table .= "<tr><td></td>";
    $bestyes = -1;
    for($i = 0; $i < count($options); $i++) {
        if($totals[$i][0] + $totals[$i][1] >= $bestyes) {
            $bestyes = $totals[$i][0] + $totals[$i][1];
        }
    }
    for($i = 0; $i < count($options); $i++) {
        $table .= "<td class=\"tot\" data-opid=\"".$i."\" data-orig-yes=\"".$totals[$i][0]."\" data-orig-maybe=\"".$totals[$i][1]."\">";
        if($totals[$i][0] + $totals[$i][1] == $bestyes)
            $table .= "<b>";
        $table .= $totals[$i][0]."(".$totals[$i][1].")";
        if($totals[$i][0] + $totals[$i][1] == $bestyes)
            $table .= "</b>";
        $table .= "</td>";
    }
    $table .= "</tr></tbody></table><br /><div id=\"saver\"><button class=\"btn btn-default\" type=\"button\" id=\"cantmake\">Cannot make it</button><button class=\"btn btn-info\" type=\"button\" id=\"save\">Save!</button></div>";
    fclose($handle);
} else {
    $table = "Error opening file";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php if(strcmp($title, "") != 0) echo $title . " | "; ?>SRCF Poll Server</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<style type="text/css">
td p {
text-align: center;
}
#saver {
text-align: right;
}
button {
margin-left: 10px;
}
td {
width: 50px;
}
</style>

<script type="text/javascript">
function updateTots() {
var most = -1;
$(".tot").each(function(){
    if(parseInt($(this).data("yes")) + parseInt($(this).data("maybe")) > most) {
        most = parseInt($(this).data("yes")) + parseInt($(this).data("maybe"))
    }
})
$(".tot").each(function(){
    var set = ""
    if(parseInt($(this).data("yes")) + parseInt($(this).data("maybe")) == most)
        set += "<b>";
    set += $(this).data("yes")
    set += " ("
    set += $(this).data("maybe")
    set += ")"
    if(parseInt($(this).data("yes")) + parseInt($(this).data("maybe")) == most)
        set += "</b>";
    $(this).html(set)
})
}

function collapse() {

}

function expand() {

}

function updateCantMake(){
    if($("#name")[0].value == "") {
        $("#save").prop("disabled", true)
        $("#cantmake").prop("disabled", true)
    } else {
        $("#save").prop("disabled", false)
    }
}

$(document).ready(function(){

  $("#name").keyup(function(e){
     updateCantMake();
     if (e.keyCode == 13) {
         sendform()
     }
  })
  updateCantMake();

  setTimeout(function(){$("#msgsuc").slideUp()}, 2000);

  $(".tot").each(function(){
      $(this).data("yes", $(this).data("orig-yes"))
      $(this).data("maybe", $(this).data("orig-maybe"))
  })

  $(".ynm").click(function(){
    if($(this).html() == "Yes") {
       $(this).parent().attr("class", "success");
       var clickedop = $(this).parent().data("option")
       $(".tot").each(function(){
           if($(this).data("opid") == clickedop) {
               $(this).data("yes", $(this).data("orig-yes") + 1)
               $(this).data("maybe", $(this).data("orig-maybe"))
           }
       })
       updateTots()
    }
    if($(this).html() == "(Yes)") {
       $(this).parent().attr("class", "warning");
       var clickedop = $(this).parent().data("option")
       $(".tot").each(function(){
           if($(this).data("opid") == clickedop) {
               $(this).data("yes", $(this).data("orig-yes"))
               $(this).data("maybe", $(this).data("orig-maybe") + 1)
           }
       })
       updateTots()
    }
    if($(this).html() == "No") {
       $(this).parent().attr("class", "danger");
       var clickedop = $(this).parent().data("option")
       $(".tot").each(function(){
           if($(this).data("opid") == clickedop) {
               $(this).data("yes", $(this).data("orig-yes"))
               $(this).data("maybe", $(this).data("orig-maybe"))
           }
       })
       updateTots()
    }
    return false;
  });

  $("#save").click(sendform);
  $("#cantmake").click(function(){
    $(".ynm").each(function(){
        $(this).parent().attr("class", "danger")
    })
    sendform()
  });
});

function sendform() {
    // submit these
    var submit = new Object();
    submit.name = $("#name")[0].value
    $(".ynm").each(function(){
        var o = $(this).parent().attr("class"), op = 0
        if(o == "success") op = 1
        if(o == "warning") op = 2
        submit[$(this).parent().data("option")] = op
    })
    $.post(window.location.href, {"data": JSON.stringify(submit)}, function(res){$("#name")[0].value= ""; window.location.reload(true);});
    $("#save").prop("disabled", true)
    $("#cantmake").prop("disabled", true)
}
</script>
  </head>
  <body>
    <div class="container" style="margin-top: 70px; margin-bottom: 50px;">
<?php
    if(isset($_SESSION['msg'])) {
        echo '<div id="msgsuc" class="alert alert-'.$_SESSION['msgtype'].'" role="alert">' . $_SESSION['msg'] . "</div>";
        unset($_SESSION['msg']);
        unset($_SESSION['msgtype']);
    }

    echo $table;
?>

    </div>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
  </body>
</html>
