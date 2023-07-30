<?php

include_once ('../utilities.php');

$in_progress = false;
$token = '';
$UserKey = '';
$UserToken = '';
$URL = '';
$CallbackURL = '';
$CallbackMethod = 'POST';
$CallbackExtraData = '';

if (chk_data()) {

    $in_progress = true;

    $UserKey = $_POST['UserKey'];
    $UserToken = $_POST['UserToken'];
    $token = get_token($UserKey, $UserToken);
    $URL = $_POST['URL'];
    $CallbackURL = $_POST['CallbackURL'];
    $CallbackMethod = $_POST['CallbackMethod'];
    if (!empty($_POST['CallbackExtraData']))
        $CallbackExtraData = $_POST['CallbackExtraData'];





}

/*******************************************************************************
 FUNCTIONS
 *******************************************************************************/    

 function chk_data(): bool {

    if (!empty($_POST)) {
        if ((!empty($_POST['UserKey'])) && (!empty($_POST['UserToken'])) && (!empty($_POST['URL']))) {
            if (!empty($_POST['CallbackURL'])) {
                if (!filter_var($_POST['CallbackURL'], FILTER_VALIDATE_URL))
                    return false;
            }    
            if (empty($_POST['CallbackMethod']) || (($_POST['CallbackMethod'] !== 'POST') && ($_POST['CallbackMethod'] !== 'GET')))
                return false;
            if (!empty($_POST['CallbackExtraData'])) {
                if (!json_decode($_POST['CallbackExtraData']))
                    return false;
            }    
            return true;
        }    
    }

    return false;

 }

?>

<!DOCTYPE html>
<html>

    <head>

        <title>Download Manager Test</title>
        <!-- Include Bootstrap CSS -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

        <!-- Include jQuery -->
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

        <style>
            #infoDiv {
                border: 1px solid #000;
                border-radius: 10px;
                background-color: #ffffcc;
                padding: 10px;
                margin-top: 2em;
            }
        </style>

    </head>

    <body>

        <div class="container">
            <div><center><h1>Download Manager Test</h1></center></div>
            <form id="myForm" action="#" method="post">
                <div class="form-group">
                    <label for="UserKey*">User Key</label>
                    <input type="text" class="form-control" id="UserKey" name="UserKey" value="<?= $UserKey ?>" required>
                </div>
                <div class="form-group">
                    <label for="UserToken*">User Token</label>
                    <input type="text" class="form-control" id="UserToken" name="UserToken" value="<?= $UserToken ?>" required>
                </div>
                <div class="form-group">
                    <label for="URL*">URL</label>
                    <input type="text" class="form-control" id="URL" name="URL" value="<?= $URL ?>" required>
                </div>
                <div class="form-group">
                    <label for="CallbackURL">Callback URL</label>
                    <input type="text" class="form-control" id="CallbackURL" name="CallbackURL" value="<?= $CallbackURL ?>">
                </div>
                <div class="form-group">
                    <label for="CallbackMethod">Callback Method</label>
                    <select class="form-control" id="CallbackMethod" name="CallbackMethod">
                        <option<?php if ($CallbackMethod === 'POST') echo ' selected'; ?>>POST</option>
                        <option<?php if ($CallbackMethod === 'GET') echo ' selected'; ?>>GET</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="CallbackExtraData">Callback Extra Data</label>
                    <textarea class="form-control" id="CallbackExtraData" name="CallbackExtraData"><?= $CallbackExtraData ?></textarea>
                </div>
                <button type="button" class="btn btn-primary" id="submitBtn">Send</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </form>

            <div id="wait-div" style="text-align: center; display: none;">
                <img style="width: 90px; height: 90px;" alt="" src="images/loading-medium.gif">
            </div>

            <div id="prgDiv" style="display: none;">
                <div id="infoDiv">
                    <center><h3>Response and Progress</h3></center>
                    <div style="font-family: 'Courier New', Courier, monospace;">
                        <p><strong>Key:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong> <span id="key"></span></p>
                        <p><strong>File name:</strong> <span id="fName"></span></p>
                        <p><strong>File size:</strong> <span id="fileSize"></span></p>
                        <p><strong>File URL:&nbsp;</strong> <span id="fURL"></span></p>
                        <p><strong>Status:&nbsp;&nbsp;&nbsp;</strong> <span id="status"></span></p>
                        <p><strong>Error:&nbsp;&nbsp;&nbsp;&nbsp;</strong> <span id="error"></span></p>
                        <p><strong>Progress:&nbsp;</strong> <span id="progressVal"></span></p>

                        <div class="progress" style="height: 3em; margin-bottom: 1.5em; margin-top: -1em;">
                            <div id="progress" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <strong id="progressPercentage"></strong>
                            </div>
                        </div>

                        <p><strong>Download:&nbsp;</strong> <span id="fDown"></span></p>
                    </div>
                </div>
            </div>

<?php if ($in_progress) { ?>
            <br>
            <center><button type="button" class="btn btn-primary" id="refreshBtn" onClick="window.location.href = '<?php echo get_curr_url(); ?>'">Restart</button></center>
<?php } ?>

            <br>

        </div>

        <!-- Include Bootstrap JS and Popper.js -->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

        <script>

            var intervalId = null;    
            var key = null;            

            $(document).ready(function() {

<?php if ($in_progress) { ?>

                sendDataToServer();
<?php } ?>

                if (<?php if ($in_progress) {echo 'true';} else {echo 'false';} ?>)
                    disableForm();

                $("#submitBtn").click(function(e) {
                    if (validateForm()) {
                        $("#myForm").submit();
                    } else {
                        e.preventDefault();
                    }
                });

                function validateForm() {

                    let userKey = $("#UserKey").val();
                    let userToken = $("#UserToken").val();
                    let url = $("#URL").val();
                    let callbackURL = $("#CallbackURL").val();
                    let callbackExtraData = $("#CallbackExtraData").val();

                    // Check if the UserKey, UserToken and URL fields are filled
                    if (!userKey) {
                        alert("Please fill out the User Key field.");
                        return false;
                    }

                    if (!userToken) {
                        alert("Please fill out the User Token field.");
                        return false;
                    }

                    // Check if the URL field is valid
                    let urlPattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
                        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
                        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
                        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
                        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
                        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator

                    if (!url || !urlPattern.test(url)) {
                        alert("Please enter a valid URL.");
                        return false;
                    }

                    if (callbackURL && !urlPattern.test(callbackURL)) {
                        alert("Please enter a valid callback URL.");
                        return false;
                    }

                    // Check if the Callback Extra Data field is a valid JSON
                    try {
                        if(callbackExtraData) {
                        JSON.parse(callbackExtraData);
                        }
                    } catch (e) {
                        alert("Please enter a valid JSON in the Callback Extra Data field.");
                        return false;
                    }

                    return true;

                }

                function disableForm() {

                    $("#myForm :input").prop("disabled", true);
                    $("#submitBtn").prop("disabled", true);
                    $("#resetBtn").prop("disabled", true);

                }

                function updatePrg() {

                    let token = '<?= $token ?>';

                    $.ajax (
                        {
                            url: '../',
                            type: 'POST',
                            async: true,
                            data: 'token=' + token + '&key=' + key,
                            dataType: 'json',
                            success: function(data) {
                                if (data['succeeded']) {
                                    $("#key").html(key);
                                    $("#fileSize").html(data['file_size']);
                                    $status = data['status'];
                                    $("#status").html($status);
                                    $("#error").html(data['err']);
                                    $("#progressVal").html(data['downloaded']);

                                    // Calculate the percentage of the file downloaded
                                    let progress = (data['downloaded'] / data['file_size']) * 100;

                                    // Set the width and aria-valuenow of the progress bar
                                    $("#progress").css("width", progress + "%").attr("aria-valuenow", progress);

                                    // Set the text of the progress percentage span
                                    $("#progressPercentage").html(Math.round(progress) + "%");

                                    $("#fName").html(data['file_name']);
                                    $("#fName").html(data['file_name']);
                                    let downloadURL = data['download_url'].trim();
                                    if (downloadURL) {
                                        downloadURL = '<a href="' + downloadURL + '" target="_blank">' + downloadURL + '</a>';
                                    }
                                    $("#fDown").html(downloadURL);
                                    if(($status == 'COMPLETED') || ($status == 'FAILED') || ($status == 'EXPIRED') || ($status == 'CANCELLED')) {
                                        clearInterval(intervalId);
                                    }
                                } else {
                                    clearInterval(intervalId);
                                    alert("Errore: " + data['err']);
                                }
                            },
                            error: function(xhr) {
                                clearInterval(intervalId);
                                alert("Errore: " + xhr.status + " " + xhr.statusText);
                            }
                        }
                    );
                    
                }

                function sendDataToServer() {

                    let token = '<?= $token ?>';
                    let url = $("#URL").val();
                    let callbackURL = $("#CallbackURL").val();
                    let callbackExtraData = $("#CallbackExtraData").html();
                    let callbackMethod = $("#CallbackMethod").val();
                    $("#wait-div").show();

                    $.ajax (
                        {
                            url: '../',
                            type: 'POST',
                            async: true,
                            data: 'token=' + token + '&url=' + url + '&callback=' + callbackURL + '&callbackData=' + callbackExtraData + '&callbackType=' + callbackMethod,
                            dataType: 'json',
                            success: function(data) {
                                if (data['succeeded']) {
                                    key = data['key'];
                                    $("#key").html(data['key']);
                                    $("#fileSize").html(data['file_size']);
                                    $("#fURL").html(url);
                                    intervalId = setInterval(updatePrg, 1000);            
                                } else {
                                    $("#wait-div").hide();
                                    alert("Errore: " + data['err']);
                                }
                            },
                            error: function(xhr) {
                                $("#wait-div").hide();
                                alert("Errore: " + xhr.status + " " + xhr.statusText);
                            },
                            complete: function(data) {
                                $("#wait-div").hide();
                                $("#prgDiv").show();
                            }
                        }
                    );
                }

            });
        </script>

    </body>

</html>
