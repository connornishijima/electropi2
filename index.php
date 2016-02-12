<?php
	// Parse without sections
	$conf = parse_ini_file("configuration.ini");
?>

<html>
	<head>
		<title>ElectroPi Automation</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
		<link href='https://fonts.googleapis.com/css?family=Oswald:400,300' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body onload="start();">
		<div id="push"></div>
		<div id="container" style="max-width:<?php echo $conf['maxWidth'];?>px;">
			<div id="header">
				<div id="logo">
					<a href="index.php"><span id="logoColor" class="offText">ELECTRO</span>PI</a>
				</div>
				<div id="settings">
					S
				</div>
			</div>
			<div id="errorBox"></div>
			<div id="nullView">
			</div>
			<div id="homeView" class="view">
				<div id="switchList">
					<div id="switchLoad">
						FETCHING SWITCHES...
					</div>
				</div>
			</div>
			<div id="settingsView" class="view">
				SETTINGS VIEW
			</div>
		</div>
	</body>
	<script>
		window["errors"] = [];
		window["currentView"] = "null";
		window["maxWidth"] = <?php echo $conf['maxWidth'];?>;

		var ws;

		window["errorDict"] = {
			100:"WS CONNECTION CLOSED!",
		};

		$( window ).resize(function() {
			checkWindow();
		});

		function WSsetup(){
			ws = new WebSocket('ws://10.0.0.88:8888/ws');
			ws.onopen = function(){
				ws.send("GET_SWITCHES");
				killError(100);
			};
			ws.onmessage = function(evt){
				console.log(evt.data);
				var items = evt.data.split(" | ");
				if(items[0] == "SWITCH_LIST"){
					$("#switchLoad").fadeOut("fast",function(){
						$(".switchLine").remove();
						var j = JSON.parse(items[1]);
						window["switches"] = j["switches"];
						var orderIndex = 0;
						var switchCount = window["switches"].length;
						while(orderIndex != switchCount){
							for(s in window["switches"]){
								var idS = window["switches"][s]["id"];
								var nickS = window["switches"][s]["nick"];
								var typeS = window["switches"][s]["type"];
								var stateS = window["switches"][s]["state"];
								var order = window["switches"][s]["order"];
								if(order == orderIndex){
									addSwitch(idS,nickS,typeS,stateS);
									orderIndex+=1;
								}
							}
						}
					});
				}
			};
			ws.onclose = function(evt){
				console.log("WS CONNECTION CLOSED! "+evt.code);
				spawnError(100);
				WSsetup();
	                };
			ws.onerror = function(evt){
				console.log("WS ERROR!");
			};
		}

		function start(){
			WSsetup();
			checkWindow();
			setInterval(checkErrors,200);
			$("#container").fadeIn("fast");
			switchView("home");
			setTimeout(function(){
				$('#logoColor').removeClass('offText').addClass('onText');
			}, 700);
		}

		function spawnError(number){
			var errorExists = false;
			for(e in window["errors"]){
				if(number == window["errors"][e]){
					errorExists = true;
				}
			}
			if(errorExists == false){
				window["errors"].push(number);
			}
		}

		function killError(number){
			var removeItem = number;
			window["errors"] = jQuery.grep(window["errors"], function(value) {
				return value != removeItem;
			});
		}

		function checkErrors(){
			if(window["errors"].length == 0){
				$("#errorBox").fadeOut("fast");
			}
			else{
				$("#errorBox").fadeIn("fast");
				errorString = "";
				for(e in window["errors"]){
					var text = window["errorDict"][window["errors"][e]];
					errorString+="ERROR ";
					errorString+=window["errors"][e];
					errorString+=": ";
					errorString+=text;
					errorString+="<br>";
				}
				$("#errorBox").html("");
				$("#errorBox").html(errorString);
			}
		}

		function addSwitch(id,name,type,state){
			var oc = 'onclick="';
			oc+= "toggleSwitch('"+id;
			oc+= "')";
			oc+= '"';
			var switchHTML =	"<div class='switchLine' id='"+id+"' "+oc+">";
			switchHTML += 			"<div class='switchState' id='"+id+"_state'>";
			switchHTML += 			"</div>";
			switchHTML += 			"<div class='switchName'>";
			switchHTML += 				name;
			switchHTML += 			"</div>";
			switchHTML += 		"</div>";
			$("#switchList").append(switchHTML);
			if(state == 1){
				$("#"+id+"_state").addClass("onBlock");
			}
			else if(state == 0){
				$("#"+id+"_state").addClass("offBlock");
			}
		}

		function toggleSwitch(id){
			for(s in window["switches"]){
                                var idS = window["switches"][s]["id"];
				if(idS == id){
					var stateS = window["switches"][s]["state"];
				}
			}

			ws.send("TOGGLE_SWITCH | "+id);
		}

		function checkWindow(){
			width = $( window ).width();
			if(width > window["maxWidth"]){
				var pushHeight = (width-window["maxWidth"])/4;
				if(pushHeight >= 30){
					pushHeight = 30;
					var headerMargin = 0;
				}
				else{
					var headerMargin = 20-(pushHeight);
				}
				$("#push").height(pushHeight);
				$("#header").css("padding-left",headerMargin).css("padding-right",headerMargin);
			}
			else{
				$("#push").height(0);
			}
		}

		function switchView(newView){
			var oldView = "#"+window["currentView"]+"View";
			$(oldView).fadeOut("fast",function(){
				$("#"+newView+"View").fadeIn("fast");
			});
			window["currentView"] = newView;
		}
	</script>
</html>
