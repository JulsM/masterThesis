import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D


def plotXWWeeklyMileage():
	data = pd.read_csv('../output/trainFeatures.csv')
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# data = data[data.isRace == 1]
			
	plt.scatter(data['XWWeeklyMileage'] / 1000, data['ngp'], c='r', s=2)

	# fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# ax = fig.add_subplot(111, projection='3d')
	# ax.scatter(data['distance'], data['XWWeeklyMileage']/1000, data['time'], c='r', s=2)
	
	# ax.set_xlabel('distance')
	# ax.set_ylabel('XWWeeklyMileage')
	# ax.set_zlabel('time')
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('weeklyMileage')
	plt.ylabel('NGP')

	plt.show()

plotXWWeeklyMileage()
