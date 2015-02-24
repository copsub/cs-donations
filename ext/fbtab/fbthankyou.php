<!DOCTYPE html>
<html>
<head>

<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >

<title>Facebook Paypal Donation</title>

<style>
body {
margin:0px;
height:1270px;
background-image: url('http://copenhagensuborbitals.com/wp_blog/wp_content/uploads/fbtab/fbbackground.png');
background-repeat: no-repeat;
}

input[type=submit] {
background-color: #ff4f00;
position:absolute;
left:45px;
top:360px;
height:67px;
width:193px;
border:none;
font: 30px helvetica, sans-serif; 
padding: 0px;
font-weight: bold; 
color: #FFFFFF;
}

input[type=submit]:hover {
background-color: #bababa;
}


input[type=checkbox] {
position:absolute;
left:45px;
top:292px;
} 

input[type=text] {
position:absolute;
left:45px;
top:210px;
font-size:20px;
width:100px;
}

select {
position:absolute;
left:165px;
top:215px;
}

</style>

<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>

<script>
function CopyFields(f) {
    f.amount.value = f.a3.value;
}

function DonationType(f) {
  	if(f.supporter.checked == true) {
    	f.cmd.value = "_xclick-subscriptions";
    	f.item_name.value = "Copenhagen Suborbitals Monthly Donation";
    	f.bn.value = "PP-SubscriptionsBF:btn_subscribeCC_LG.gif:NonHosted";
	} else {
    	f.cmd.value = "_donations";
    	f.item_name.value = "Copenhagen Suborbitals Single Donation";
    	f.bn.value = "PP-DonationsBF:btn_donateCC_LG.gif:NonHosted";
	}	
}


$.get("http://ipinfo.io", function (response) {
	if (response.country == "DK") {
	$("#currency_code").val("DKK");
	}
	if (response.country == "DE") {
	$("#currency_code").val("EURO");
	}	
	if (response.country == "SE") {
	$("#currency_code").val("EURO");
	}
	if (response.country == "NO") {
	$("#currency_code").val("EURO");
	}	
	if (response.country == "ES") {
	$("#currency_code").val("EURO");
	}
	if (response.country == "US") {
	$("#currency_code").val("USD");
	}	
}, "jsonp");

</script>



</head>
<body>

<div style="position:releative;">

<!-- "Donate" Helvetica Bold 95.15 -->
<div style="position:absolute;left:39px;top:83px;font: 95px helvetica, sans-serif; font-weight: bold; color: #FFFFFF;padding:0px;margin:0px;">
	Donate
</div>  

<!-- "How much..." Helvetica Regular 20 -->
<div style="position:absolute;left:45px;top:180px;font: 20px helvetica, sans-serif; color: #FFFFFF;">
	How much would you like to give?
</div>  

<!-- "Make this a month..." Helvetica Regular 20 -->
<div style="position:absolute;left:75px;top:293px;font: 20px helvetica, sans-serif; color: #ff4f00;padding:0px;margin:0px;">
	<label for="supporter">
	Make this a monthly donation
	</label>
</div> 
 

<!-- "Even though..." Helvetica Regular 18 -->
<div style="position:absolute;left:45px;top:485px;width:310px;font: 18px helvetica, sans-serif; color: #FFFFFF;line-height: 30px;">
	Even though everyone in Copenhagen Suborbitals are working for free, we need earth money to build spaceships. Tools, rent and materials are not free. This is a 100 % crowdfunded project. we need you to get into space! 
</div> 


<!-- "Thanks" Helvetica Regular 80 -->
<div style="position:absolute;left:505px;top:1165px;font: 80px helvetica, sans-serif; color: #FFFFFF;margin:0px;padding:0px;">
	Thanks
</div> 

<!-- "Now we're closer" Helvetica Regular 20 -->
<div style="position:absolute;left:613px;top:1240px;font: 20px helvetica, sans-serif; color: #ff4f00;">
	Now we're closer
</div> 


<!-- Dividerline 1 -->
<div style="position:absolute;left:45px;top:276px;width:310px;background:url('http://copenhagensuborbitals.com/wp_blog/wp_content/uploads/fbtab/dividerline.png') no-repeat;height:1px; ">

</div>

<!-- Dividerline 2 -->
<div style="position:absolute;left:45px;top:326px;width:310px;background:url('http://copenhagensuborbitals.com/wp_blog/wp_content/uploads/fbtab/dividerline.png') no-repeat;height:1px; ">

</div> 

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
	<input type="hidden" name="cmd" value="_donations">
	<input type="hidden" name="business" value="6D4XG7HWPHG9U">
	<input type="hidden" name="lc" value="US">
	<input type="hidden" name="item_name" value="Copenhagen Suborbitals Single Donation">
	<input type="hidden" name="item_number" value="1234"> <!-- not in single donation -->
	<input type="hidden" name="no_note" value="1">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="src" value="1">	<!-- not in single donation -->
	<input type="hidden" name="p3" value="1">   <!-- not in single donation -->
	<input type="hidden" name="t3" value="M">   <!-- not in single donation -->
	<input type="hidden" name="amount"  value="20">
	<input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_subscribeCC_LG.gif:NonHosted">
	<input type="checkbox" id="supporter" name="supporter" value="supporter" onchange="DonationType(this.form)" style="">
	<input type="text" name="a3"  value="20"  onchange="CopyFields(this.form)" style="">

<select name="currency_code" id="currency_code">
  <option value="USD">USD</option>
  <option value="DKK">DKK</option>
  <option value="EUR">EURO</option>
</select>	
	
	<input type="submit" value="Donate" style="" />

	<img alt=""  src="https://www.paypalobjects.com/da_DK/i/scr/pixel.gif" width="1" height="1">
</form>

</div>

</body>
</html>