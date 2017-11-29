import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing




def plotSet():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']
	
	
	fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	ax = fig.add_subplot(111, projection='3d')

	
	
	ax.scatter(data['distance'], data['elevation'], data['time'], c='r', s=2)


	plt.legend(fontsize="small", loc="upper right")
	ax.set_xlabel('distance')
	ax.set_ylabel('elevation')
	ax.set_zlabel('time')
	plt.grid()
	plt.show()

	

def plotElevSpeed():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	for i in range(8, 80):
		filtered = data[(data.distance > i * 500 - 250) & (data.distance < i * 500 + 250)]
		if len(filtered > 0):
			std_scale = preprocessing.StandardScaler().fit(filtered)
			filtered = std_scale.transform(filtered)
			plt.scatter(filtered[:, 1], filtered[:, 2], c='grey', s=2, label='')
	plt.xlabel('elevation')
	plt.ylabel('time')
	plt.legend()
	plt.grid()
	plt.show()

def plotXWWeeklyMileage():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# data = data[data.isRace == 1]
			
	plt.scatter(data['XWWeeklyMileage'] / 1000, data['ngp'], c='r', s=2)
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('weeklyMileage')
	plt.ylabel('NGP')

	plt.show()

def plotXWElevation():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	for i in range(0, 30):
		filtered = data[(data.elevation > i * 50 - 25) & (data.elevation < i * 50 + 25)]
		if len(filtered > 0):
			std_scale = preprocessing.StandardScaler().fit(filtered)
			filtered = std_scale.transform(filtered)
			plt.scatter(filtered[:, 3], filtered[:, -1], c='grey', s=2, label='')
	
			
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('Elevation gain')
	plt.ylabel('NGP')

	plt.show()

def plotXWSpeedwork():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	data = data[data.isRace == 1]
			
	plt.scatter(data['speedwork'], data['ngp'], c='r', s=2)
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('number speedwork')
	plt.ylabel('NGP')

	plt.show()

def plotXWLongRuns():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print('samples: ',len(data))
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	data = data[data.distance > 9500]
	# data = data[data.isRace == 1]


			
	for i in range(18, 80):
		filtered = data[(data.distance > i * 1000 - 500) & (data.distance < i * 1000 + 500)]
		if len(filtered > 0):
			std_scale = preprocessing.StandardScaler().fit(filtered[['time']])
			filtered[['time']] = std_scale.transform(filtered[['time']])
			plt.scatter(filtered['long'], filtered['time'], c='grey', s=2, label='')
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('number long runs')
	plt.ylabel('time')

	plt.show()

def plotXWTrainPace():
	data = pd.read_csv('../output/activitySetFeatures.csv')
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	
	data = data[data.avgTrainPace != 0]
	data = data[(data.isRace == 1) & (data.distance > 20900) & (data.distance > 21300)]
	print('samples: ',len(data))
	# fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# ax = fig.add_subplot(111, projection='3d')

	
	
	# ax.scatter(data['distance'], data['trainPace'], data['time'], c='r', s=2)


	plt.scatter(data['avgTrainPace'], data['time'], c='grey', s=2, label='')
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('avgTrainPace')
	plt.ylabel('time')

	plt.show()

def plotXWAvgVo2max():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	
	data = data[data.vo2max != 0]
	# data = data[data.isRace == 1]
	print('samples: ',len(data))
	# fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# ax = fig.add_subplot(111, projection='3d')

	
	
	# ax.scatter(data['distance'], data['trainPace'], data['time'], c='r', s=2)


	plt.scatter(data['vo2max'], data['ngp'], c='grey', s=2, label='')
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('vo2max')
	plt.ylabel('NGP')

	plt.show()

# plotXWWeeklyMileage()
# plotElevSpeed()
# plotXWElevation()
# plotXWSpeedwork()
# plotXWLongRuns()
plotXWTrainPace()
# plotXWAvgVo2max()

# plotSet()
