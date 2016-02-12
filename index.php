<html>
	<head>
		<title>ElectroPi Automation</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
		<link href='https://fonts.googleapis.com/css?family=Oswald:400,300' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" type="text/css" href="style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body onload="start();">
		<div id="push"></div>
		<div id="container" style="max-width:6400px;">
			<div id="header">
				<div id="logo" class="noSelect">
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
				<div id="switchList" class="noSelect">
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
		window["configLoaded"] = false;

		window["conf"] = {};

		var ws;

		window["errorDict"] = {
			100:"WS CONNECTION CLOSED!",
			101:"Go fuck yourself",
		};

		$( window ).resize(function() {
			checkWindow();
		});

		function WSsetup(){
			ws = new WebSocket('ws://10.0.0.88:8888/ws');
			ws.onopen = function(){
				ws.send("GET_SESSION");
				killError(100);
			};
			ws.onmessage = function(evt){
				console.log(evt.data);
				var items = evt.data.split(" | ");
				if(items[0] == "CONFIG"){
					if(window.configLoaded == false){
						window.configLoaded = true;
						$("#container").fadeIn("fast");
					}
					window.conf = JSON.parse(items[1]);
					$("#container").css("max-width",window.conf.maxWidth);
					checkWindow();
				}
				if(items[0] == "SWITCH_LIST"){
					window["switchesLast"] = window["switches"];
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
							try{
								var stateSlast = window["switchesLast"][s]["state"];
							}
							catch(err){
								var stateSlast = stateS;
							}
							var order = window["switches"][s]["order"];
							if(order == orderIndex){
								addSwitch(idS,nickS,typeS,stateS);
								if(stateS!=stateSlast){
									if(stateS == 0){
										$("#"+idS).css('background-color','#ff5c93');
										$("#"+idS).animate({backgroundColor:'#242424'}, 600);
									}
									else if(stateS == 1){
										$("#"+idS).css('background-color','#00ffbe');
										$("#"+idS).animate({backgroundColor:'#242424'}, 600);
									}
								}
								orderIndex+=1;
							}
						}
					}
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
			setInterval(checkErrors,200);
			switchView("home");
			setTimeout(function(){
				$('#logoColor').animate({color:'#00ffbe'}, 400);
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
			navigator.vibrate(10);
			ws.send("TOGGLE_SWITCH | "+id);
			for(s in window["switches"]){
                                var idS = window["switches"][s]["id"];
				if(idS == id){
					var stateS = window["switches"][s]["state"];
					if(stateS == 0){
						stateS = 1;
						$("#"+id+"_state").addClass("onBlock");
						$("#"+id).css('background-color','#00ffbe');
						$("#"+id).animate({backgroundColor:'#242424'}, 600);
					}
					else if(stateS == 1){
						stateS = 0;
						$("#"+id+"_state").addClass("offBlock");
						$("#"+id).css('background-color','#ff5c93');
						$("#"+id).animate({backgroundColor:'#242424'}, 600);
					}
//					window["switches"][s]["state"] = stateS;
				}
			}
		}

		function checkWindow(){
			width = $( window ).width();
			if(width > window.conf.maxWidth){
				var pushHeight = (width-window.conf.maxWidth)/4;
				if(pushHeight >= 30){
					pushHeight = 30;
					var headerMargin = 0;
				}
				else{
					var headerMargin = (20-(pushHeight))/2;
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
