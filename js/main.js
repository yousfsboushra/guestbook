function auth(form){
    resetFormMessagess(form); // Clear all errors and messages
    let username = $(form).find("input[name='username']").val();
    let password = $(form).find("input[name='password']").val();
    let api = $(form).find("input[name='api']").val();

    // Send data to the server
    $.ajax({
        url: "api/auth.php",
        method: 'POST',
        data: {
            api: api,
            username: username,
            password: password
        },
        success: function( json ) {
            let result = JSON.parse(json);
            if(result.error){ // Display error
                displayMessage(form, result.response, 'error');
            }else{ // Display success message
                if(api == "login"){
                    localStorage.setItem("userId", result.response.id);
                    localStorage.setItem("username", result.response.username);
                    localStorage.setItem("userToken", result.response.token);
                    setTimeout(function(){
                        window.location.href = 'index.html';
                    }, 1000);
                }else{
                    displayMessage(form, result.response, 'info');
                    setTimeout(function(){
                        window.location.href = 'login.html';
                    }, 1000);
                }
            }
        }
    });
    return false;
}

function submitMessage(form){
    resetFormMessagess(form); // Clear all errors and messages
    if(localStorage.userToken != undefined){
        let message_id = $(form).find("input[name='message_id']").val();
        let message = $(form).find("textarea[name='message']").val();
        let reply_to = $(form).find("input[name='reply_to']").val();
        let recipient_id = $(form).find("select[name='recipient_id']").val();
        let api = $(form).find("input[name='api']").val();

        // Send data to the server
        $.ajax({
            url: "api/messages.php",
            method: 'POST',
            data: {
                message_id: (message_id != undefined && message_id != "")? message_id : '0',
                message: (message != undefined && message != "")? message : '',
                api: api,
                reply_to: (reply_to != undefined && reply_to != "")? reply_to : '0',
                recipient_id: (recipient_id != undefined && recipient_id != "")? recipient_id : '0',
                token: localStorage.userToken
            },
            success: function( json ) {
                let result = JSON.parse(json);
                if(result.error){ // Display error
                    displayMessage(form, result.response, 'error');
                }else{ // Display success message
                    displayMessage(form, result.response, 'info');
                    setTimeout(function(){
                        window.location.href = 'messages.html';
                    }, 400);
                }
            }
        });
    }
    return false;
}

function logout(){
    localStorage.removeItem("username");
    localStorage.removeItem("UserId");
    localStorage.removeItem("userToken");
    window.location.href = 'login.html';
}

function checkIfLoggedIn(){
    if(localStorage.userToken == undefined){
        window.location.href = 'login.html';
    }else{
        window.location.href = 'messages.html';
    }
}

function listUsers(){
    if(localStorage.userToken != undefined){
        $.ajax({
            url: "api/users.php",
            method: 'POST',
            data: {
                api: "list",
                token: localStorage.userToken
            },
            success: function( json ) {
                let result = JSON.parse(json);
                if(result.error){ // Display error

                }else{ // Display success message
                    let users = result.response;
                    let options = "";
                    for(let userId in users){
                        options += "<option value='" + userId + "'>" + users[userId] + "</option>";
                    }
                    $("#field_recipient_id").html(options);
                }
            }
        });
    }
}

function loadMessages(){
    if(localStorage.username != undefined){
        $("#username").html(localStorage.username);
    }
    if(localStorage.userToken != undefined){
        $.ajax({
            url: "api/messages.php",
            method: 'POST',
            data: {
                api: "list",
                token: localStorage.userToken
            },
            success: function( json ) {
                let result = JSON.parse(json);
                if(result.error){ // Display error
                    let messages = "Error loading messages";
                    $("#messages-list").html(messages);
                }else{ // Display success message
                    let users = result.response;
                    if(users.length == 0){
                        $("#messages-list").html("No messages");
                    }else{
                        let html = "";
                        for(let index in users){
                            html += '<fieldset class="user-item">';
                            html += '<legend class="user-name">' + users[index].name + '</legend>';
                            html += '<div class="user-messages">';
                                let messages = users[index].messages;
                                for(let messageIndex in messages){
                                    html += '<div class="message-item ' + ((parseInt(localStorage.userId) == parseInt(messages[messageIndex].from))? 'right' : 'left') + '">';
                                    html += '<div class="message-item-text">';
                                    if(messages[messageIndex].reply_to != undefined && messages[messageIndex].reply_to != "" && !isNaN(messages[messageIndex].reply_to)){
                                        html += 'Reply to #' + messages[messageIndex].reply_to + ': ';
                                    }
                                    html += messages[messageIndex].message;
                                    html += '</div>';
                                    html += '<div class="message-item-links">';
                                    if(parseInt(localStorage.userId) == parseInt(messages[messageIndex].from)){
                                        html += '<a data-message="' + messages[messageIndex].message + '" data-messageid="' + messageIndex + '" data-messageto="' + messages[messageIndex].to + '" onClick="openEditMessage(this);">Edit</a>';
                                        html += ' <a data-messageid="' + messageIndex + '" onClick="deleteMessage(this);">Delete</a>';
                                    }else{
                                        html += ' <a data-messageid="' + messageIndex + '"  data-messageto="' + messages[messageIndex].from + '" onClick="replyToMessage(this);">Reply</a>';
                                    }
                                    html += '</div>';
                                    html += "</div>";
                                }
                            html += "</div>";
                            html += "</fieldset>";
                        }
                        $("#messages-list").html(html);
                    }
                }
            }
        });
    }
}

function deleteMessage(element){
    let message_id = $(element).attr('data-messageid');
    if(localStorage.userToken != undefined){
        if(confirm("Are you sure?")){
            $.ajax({
                url: "api/messages.php",
                method: 'POST',
                data: {
                    api: "delete",
                    message_id: message_id,
                    token: localStorage.userToken
                },
                success: function( json ) {
                    let result = JSON.parse(json);
                    if(result.error){ // Display error
                        alert(result.response);
                    }else{ // Display success message
                        $(element).parents('.message-item').first().hide();
                    }
                }
            });
        }
    }
}

function openEditMessage(element){
    let message = $(element).attr('data-message');
    let messageId = $(element).attr('data-messageid');
    let messageTo = $(element).attr('data-messageto');
    $("#field_recipient_id").val(messageTo);
    $("#field_text").val(message);
    $("#field_message_id").val(messageId);
    $("#field_api").val('update');
}

function replyToMessage(element){
    let messageId = $(element).attr('data-messageid');
    let messageTo = $(element).attr('data-messageto');
    $("#field_reply_to").val(messageId);
    $("#field_recipient_id").val(messageTo);
    //$("#field_text").val();
    $("#field_message_id").val('');
    $("#field_api").val('reply');
}

// Display success and error messages
function displayMessage(form, message, type){
    $formMessage = $(form).find('.form-' + type).first();
    $formMessage.html(message);
}

// Clear all errors and messages
function resetFormMessagess(form){
    $(form).find('.form-error').first().html("");
    $(form).find('.form-info').first().html("");
}