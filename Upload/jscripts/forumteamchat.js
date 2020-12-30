document.getElementById("forOpen").onclick = function() {
	$('#forOpen').hide();
	$('#forClose').show();
	$(".bdy").show();
	var ele = document.getElementById("chatbody");
	ele.scrollTop = a.scrollHeight;
}
document.getElementById("forClose").onclick = function() {
	$('#forOpen').show();
	$('#forClose').hide();
	$(".bdy").hide();
}	
$("#chatform").submit(function(e) {
e.preventDefault();
document.getElementById("send").disabled = true;
var url = $("#chatform").data("route");
var fde = new FormData(this);
	$.ajax({
		url: url,
		type: "POST",
		data: fde,
		dataType: "json",
		success: function(data) {
			$("#chatform input[type='text']").val("");
			if(data.response=="sent") {
		  		$("body").load("#chatbody", function() {
		  			var ele = document.getElementById("chatbody");
					ele.scrollTop = ele.scrollHeight;
					$("#chatform input[type='text']").focus();					
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
});

function msgClick(e) {
	var dum = e.getAttribute("dummy");
	var did = e.id;
	if(did!="msgDivA") {
		e.style.color = "#fff";
	}
	document.getElementById(dum).style.visibility = "visible";
	document.getElementById(dum).style.transition = "0.4s";
	e.style.background = "#262626";
}

function msgOff(e) {
	var div = e.id;
	var dum = e.getAttribute("dummy");
	document.getElementById(dum).style.visibility = "hidden";
	document.getElementById(dum).style.transition = "0.4s";
	if(div=="msgDivA") {
		e.style.background = "rgb(0,132,255)";
	} else {
		e.style.background = "";
		e.style.color = "";
	}
}

window.onload = function() {
var onele = document.getElementById("chatbody");
onele.scrollTop = onele.scrollHeight;
};
//Get messages every 10sec Live Chat Update Time
var handle = setInterval(function() {
var hele = document.getElementById("chatbody");
hele.scrollTop = hele.scrollTop;
$("#chatbody").load(location.href + " #chatbodymsg");
}, 10000);