$("#chatform").submit(function(e) {e.preventDefault();
document.getElementById("send").disabled = true;
var url = $("#chatform").data("route");
var fde = new FormData(this);
$.ajax({url: url,type: "POST",data: fde,dataType: "json",success: function(data) {$("#chatform input[type='text']").val("");
if(data.response=="sent") {$("body").load("#chatbody", function() {$('.bdy').show();
var ele = document.getElementById("chatbody");
ele.scrollTop = ele.scrollHeight;
$("#chatform input[type='text']").focus();
});
} else {}},error: function(err) {},cache: false,contentType: false,processData: false,});
});
function msgClick(e) {var dum = e.getAttribute("dummy");
var did = e.id;
if(did!="msgDivA") {e.style.color = "#fff";
}document.getElementById(dum).style.visibility = "visible";
document.getElementById(dum).style.transition = "0.4s";
e.style.background = "#262626";
};
function msgOff(e) {var div = e.id;
var dum = e.getAttribute("dummy");
document.getElementById(dum).style.visibility = "hidden";
document.getElementById(dum).style.transition = "0.4s";
if(div=="msgDivA") {e.style.background = "rgb(0,132,255)";
} else {e.style.background = "";
e.style.color = "";
}}
window.onload = function() {var onele = document.getElementById("chatbody");
onele.scrollTop = onele.scrollHeight;
};

var handle = setInterval(function() {var hele = document.getElementById("chatbody");
hele.scrollTop = hele.scrollTop;
$("#chatbody").load(location.href + " #chatbodymsg");
}, 10000);

function dotsVis(e) {$('.dots').css('visibility','hidden');
var msgid = e.getAttribute('msgid');
var dots = e.querySelector('.dots');
dots.style.visibility = "visible";
}
function rmvIt(e) {$('.remove-message').css('visibility','hidden');
var msgid = e.getAttribute('msgid');
ele = "remove-"+msgid;
$('.'+ele).css('visibility','visible');
$('.'+ele).css('visibility','visible');
$('.'+ele).css('visibility','visible');
$('.'+ele).on('click',function() {removeMSG(msgid);
});
}
function removeMSG(msgid) {
	var url = $("#chatform").data("route");
	var fde = new FormData();
	fde.append('msgid',msgid);
	fde.append('action',"remove");
	$.ajax({
		url: url,
		type: "POST",
		data: fde,
		dataType: "json",
		success: function(data) {
			if(data.response=="removed") {
				$("body").load("#chatbody", function() {
					$('.bdy').show();
					var ele = document.getElementById("chatbody");
					ele.scrollTop = ele.scrollHeight;
				});
			} else {

			}
		},
		error: function(e) {

		},
		cache: false,
		contentType: false,
		processData: false,
	});
};


$(document).ready(function() {$('.scrolltobottom').on('click',function(e) {var ele = document.getElementById("chatbody");
ele.scrollTop = ele.scrollHeight;
});
$('.clear-chathistory').on('click',function(e) {if(confirm('Do you really want to clear chat history?')) {var url = $("#chatform").data("route");
var fde = new FormData();
fde.append('action',"clearchat");
$.ajax({url: url,type: "POST",data: fde,dataType: "json",success: function(data) {if(data.response=="cleared") {$("body").load("#chatbody", function() {$('.bdy').show();
var ele = document.getElementById("chatbody");
ele.scrollTop = ele.scrollHeight;});
}},cache: false,contentType: false,processData: false,});
} else {return false;
}});
});
