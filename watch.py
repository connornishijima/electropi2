from tornado import websocket, web, ioloop
import traceback
import threading
from threading import Thread
import json
import os
import commands
print "IMPORTS COMPLETE."
import time

myPID = str(os.getpid())
print myPID

connections = set()

global lastMessage
lastMessage = time.time()

print "KILLING ALL PROCESSES ON PORT 8888..."
os.system("fuser -k 8888/tcp")
print "DONE."

'''
ports = commands.getoutput("sudo lsof -i :8888").split("\n")
for item in ports:
	print item
        item = item.split(" ")
        i = 0
        x = ""
        while i < 10:
                try:
                        if item[i] == "pi":
                                x = item[i-3]
                                i = 99
                                print x
                                if x != myPID:
                                        print "I AM "+myPID+", KILLING "+x
                                        os.system("sudo kill -9 "+x)
                                else:
                                        print "I AM "+myPID+", NOT KILLING SELF"
                                time.sleep(1)
                except:
                        pass
                i+=1
'''

class SocketHandler(websocket.WebSocketHandler):

    def check_origin(self, origin):
	return True

    def open(self):
        if self not in connections:
            connections.add(self)

    def on_message(self, message):
	global lastMessage
	lastMessage = time.time()
	print message
	message = message.split(" | ")
	if message[0] == "GET_SESSION":
		print "Sending switches and config to client..."
		with open("switches.json","r") as f:
			data = f.read();
		self.write_message("SWITCH_LIST | "+data)
		with open("config.json","r") as f:
			data = f.read();
		self.write_message("CONFIG | "+data)

	if message[0] == "TOGGLE_SWITCH":
		id = message[1]
		with open("switches.json","r") as f:
                        data = json.loads(f.read())
		state = 99
		for item in data["switches"]:
			if item["id"] == id:
				state = item["state"]
				if state == 0:
					state = 1
				elif state == 1:
					state = 0
				item["state"] = state
		with open("switches.json","w") as f:
			f.write(json.dumps(data, indent=4))

		print "Sending new switch states to clients..."
		for con in connections:
			con.write_message("SWITCH_LIST | "+json.dumps(data))

    def on_close(self):
        if self in connections:
	    connections.remove(self)

app = web.Application([
    (r'/ws', SocketHandler),
])

def tornadoLoop():
    app.listen(8888)
    ioloop.IOLoop.instance().start()

def mainLoop():
	global lastMessage
	switches = {}
	switchesLast = {}
	switchesFirstRun = True

	while True:
		try:
			time.sleep(0.1)
			with open("switches.json","r") as f:
				switches = json.loads(f.read())
			if switches != switchesLast:
				switchesLast = switches
				if time.time()-lastMessage > 2:
					if switchesFirstRun == False:
						print "SWITCHES UPDATED REMOTELY! Telling clients..."
						for con in connections:
	                        			con.write_message("SWITCH_LIST | "+json.dumps(switches))
				switchesFirstRun = False
		except:
			print "FUCK:"
			traceback.print_exc()
	print "GO"

if __name__ == '__main__':
	Thread(target = tornadoLoop).start()
	Thread(target = mainLoop).start()
