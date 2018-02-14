import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing
import scipy

data = pd.read_csv('../output/Julian Maurer/activitySetFeatures.csv')
print('samples: ',len(data))
colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']

def plotSet():
	
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
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	d = data[(data.elevation < 1000) & (data.time < 1000) & (data.time > 0)]
	# for i in range(8, 80):
	# 	filtered = data[(data.distance > i * 500 - 250) & (data.distance < i * 500 + 250)]
	# 	if len(filtered > 0):
	# 		std_scale = preprocessing.StandardScaler().fit(filtered)
	# 		filtered = std_scale.transform(filtered)
	# 		plt.scatter(filtered[:, 1], filtered[:, 2], c='grey', s=2, label='')
	vel = d['distance']/(d['time'] * 60)
	fit = np.polyfit(d['elevation'], vel, deg=1)
	plt.plot(d['elevation'], fit[0] * d['elevation'] + fit[1], color='red')
	plt.scatter(d['elevation'], vel, c='purple', s=1, label='')
	plt.xlabel('Elevation (m)')
	plt.ylabel('Velocity (m/s)')
	# plt.legend()
	plt.grid()
	plt.show()

def plotWeeklyMileage():
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# data = data[data.isRace == 1]
			
	plt.scatter(data['mile'] / 1000, data['avgTrainPace'], c='coral', s=2)
	fit = np.polyfit(data['mile'] / 1000, data['avgTrainPace'], deg=1)
	plt.plot(data['mile'] / 1000, fit[0] * (data['mile'] / 1000) + fit[1], color='red')
	# plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('weeklyMileage')
	plt.ylabel('avgTrainPace')

	plt.show()



def plotXWAvgVo2max():
	
	# data = pd.read_csv('../output/Alexander Luedemann/trainFeatures.csv')
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	
	d = data[(data.avgVo2max != 0) & (data.time > 0) ]
	
	# vel = d['distance']/(d['time'] * 60)
	fit = np.polyfit(d['avgVo2max'], d['ngp'], deg=1)
	plt.plot(d['avgVo2max'], fit[0] * d['avgVo2max'] + fit[1], color='red')

	plt.scatter(d['avgVo2max'], d['ngp'], c='purple', s=1, label='')
	
	# plt.legend(fontsize="small", loc="upper right")
	plt.grid()
	plt.xlabel('Avg Vo2max last 6 weeks')
	plt.ylabel('NGP m/s')

	plt.show()

def plotElevHilly():
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	d = data[(data.elevation < 500)]

	
	fit = np.polyfit(d['elevation'], d['hilly'], deg=1)
	plt.plot(d['elevation'], fit[0] * d['elevation'] + fit[1], color='red')

	plt.scatter(d['elevation'], d['hilly'], c='purple', s=1, label='')
	
	
	plt.grid()
	plt.xlabel('Elevation')
	plt.ylabel('Percentage hilly')

	plt.show()

def plotHillyCS():
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# d = data[(data.elevation < 500)]
	d = data

	
	fit = np.polyfit(d['hilly'], d['climbScore'], deg=1)
	plt.plot(d['hilly'], fit[0] * d['hilly'] + fit[1], color='red')

	plt.scatter(d['hilly'], d['climbScore'], c='purple', s=1, label='')
	
	
	plt.grid()
	plt.xlabel('Percentage hilly')
	plt.ylabel('Climb Score')

	plt.show()

def plotTsbNgp():
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	d = data[(data.atl < 150) & (data.atl > 0) & (data.ctl < 150) & (data.ctl > 0) ]
	# d = data
	tsb = d['ctl'] - d['atl']
	
	fit = np.polyfit(tsb, d['ngp'], deg=1)
	plt.plot(tsb, fit[0] * tsb + fit[1], color='red')

	plt.scatter(tsb, d['ngp'], c='purple', s=1, label='')
	
	
	plt.grid()
	plt.xlabel('Training Stress Balance')
	plt.ylabel('NGP')

	plt.show()

# plotWeeklyMileage()
# plotElevSpeed()
# plotXWAvgVo2max()
plotElevHilly()
# plotHillyCS()
# plotTsbNgp()

# plotSet()
