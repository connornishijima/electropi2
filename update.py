import os

print "Enter commit message:"
commit = raw_input();

os.system("git add *")
os.system("git commit -m '"+commit+"'")
os.system("git push origin master")
