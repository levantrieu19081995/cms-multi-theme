function show_image(src, width, height, alt) {
    var img = document.createElement("img");
    img.src = src;
    img.width = width;
    img.height = height;
    img.alt = alt;

    // This next line will just add it to the <body> tag
    document.body.appendChild(img);
}

function imageExists(url, callback) {
  var img = new Image();
  img.onload = function() { callback(true); };
  img.onerror = function() { callback(false); };
  img.src = url;
}

function pathStrip(url){
    ax= url.lastIndexOf('./')+1;
    return ax? url.substring(ax+1):url;
}

function CopyMe(oFileInput, area) {
    var filePath = oFileInput.value;
    document.getElementByID(area).innerHTML = "<?php Read(filePath);?>";
    }

function del_Dir(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
if (url=="") return false;
var c=window.confirm("If you continue This will delete ALL ITEMS inside choosen folder. PRESS CANCEL to break operation.");
if (c == false) return false;

 var parameters="dir="+ url;
    xmlhttp.open('POST', adr+'commands/delete_dir.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function create_Dir(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var new_dir= prompt("Please enter name of NEW Directory", "");
if (new_dir==null) return false;
var url=document.getElementById('full').value+new_dir;
 var parameters="dir="+ url;
    xmlhttp.open('POST', adr+'commands/create_dir.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function rename_Dir(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
var new_dir= prompt("Please enter name for Directory\nChosen directory is\n"+url, "");
if (new_dir==null) return false;
var segments = url.split("/");
var rot= (segments.length > 1) ? segments[segments.length - 2] : "";
rot=url.replace(rot +"/", "");
new_dir=rot+new_dir;

 var parameters="dir="+ url +"&new_name="+ new_dir;
    xmlhttp.open('POST', adr+'commands/rename_dir.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}


function move_Dir(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
var new_dir= prompt("Please enter FULL path WHERE you wana move chosen directory\nChosen directory to move is"+url+"/nNOTE: write complete new path with same name AND final backslash (/)", "");
if (new_dir==null) return false;

 var parameters="dir="+ url +"&new_name="+ new_dir;
    xmlhttp.open('POST', adr+'commands/move_dir.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function change_dir_permision(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;

var new_perm= prompt("Please enter NEW permission for choosen Directory ( eg 777,or 755 etc)", "");
if (new_perm==null) return false;

 var parameters="dir="+ url +"&new_perm="+"2"+new_perm;
    xmlhttp.open('POST', adr+'commands/change_permisions.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}


function create_file(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;

var new_name= prompt("Please enter NAME with extension for NEW file( eg Readme.txt)", "");
if (new_name==null) return false;

 var parameters="dir="+ url +"&new_name="+new_name;
    xmlhttp.open('POST', adr+'commands/file_create.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function save_file(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
var text1 = document.getElementById('editor').value;

 var parameters="dir="+ url +"&text="+text1;
    xmlhttp.open('POST', adr+'commands/file_save.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function delete_file(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
var file=url.split('\\').pop().split('/').pop();
var c=window.confirm("If you continue This will delete FILE: "+file +"\nPRESS CANCEL to break operation.");
if (c == false) return false;
 var parameters="dir="+ url ;
    xmlhttp.open('POST', adr+'commands/file_delete.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}

function rename_file(adr)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            alert( xmlhttp.responseText);
location.reload();
        }
    }
var url=document.getElementById('full').value;
var file=url.split('\\').pop().split('/').pop();
var c=window.confirm("If you continue This will rename FILE: "+file +"\nPRESS CANCEL to break operation.");
if (c == false) return false;
var new_name= prompt("Please enter new NAME for ths file", file);
if (new_name==null) return false;

 var parameters="dir="+ url +"&new_name=" + new_name +"&old_name=" + file ;
    xmlhttp.open('POST', adr+'commands/rename_file.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}


function prepare_query()
{
document.getElementById('editor').value="";
document.getElementById("editor").value="Enter query code than press QUERY button";
document.getElementById("editor").focus();

}



function run_query(adr,server,user,pass,base)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
document.getElementById('editor').value=xmlhttp.responseText;
        }
    }
var text=document.getElementById('editor').value;
text=text.replace(/\+/g, "~");

var c=window.confirm("If you continue This will run dangerous php code!!!\nPRESS CANCEL to break operation.");
if (c == false) return false;
 var parameters="string="+ text + "&server="+server + "&user="+user + "&pass="+pass + "&base="+base;
    xmlhttp.open('POST', adr+'commands/eval_php.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}






function send_to_editor(funcNum,url,ThisIs,EditorAskFor)
{
if (ThisIs==EditorAskFor) {
window.top.opener.CKEDITOR.tools.callFunction(funcNum, url);
alert(ThisIs+"\nis In editor");}
else alert("Editor search for "+ EditorAskFor+", and you sed to him "+ ThisIs +"\nThat is wrong... ");

}

function start_upload(adr,full)
{
  if (window.XMLHttpRequest)
    {
        xmlhttp = new XMLHttpRequest();
    }
    else
    {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
document.getElementById('editor').value=xmlhttp.responseText;
        }
    }
var full=document.getElementById('fullForm').value;
alert(full);

 var parameters="adr="+adr +"&patha="+ full;
alert (parameters)
    xmlhttp.open('POST', adr+'zsmanager/upload_back.php', true);
    xmlhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    xmlhttp.send(parameters);
}
