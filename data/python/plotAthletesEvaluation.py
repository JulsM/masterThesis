import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing

label = ['Athlete 1', 'Athlete 2','Athlete 3','Athlete 4','Athlete 5','Athlete 6','Athlete 7','Athlete 8','Athlete 9','Athlete 10','Athlete 11','Athlete 12',]
	
colors = ['dodgerblue', 'greenyellow', 'coral', 'darkorchid', 'darkorange', 'mediumturquoise', 'moccasin',  
'limegreen', 'blueviolet',  'palevioletred', 'palegreen', 'indianred']

def plotData():	
	data = pd.read_csv('../output/Julian Maurer/athletesEvaluation.csv', skipinitialspace=True)
	
	fig = plt.figure(figsize=(14, 8), dpi=100, facecolor='w')

	plt.subplot(311)
	gender = data.loc[:,'gender'].value_counts()
	plt.axis('equal')
	plt.pie(gender, labels=gender.index.tolist(), autopct='%1.1f%%', colors=colors, 
		radius=1.2, wedgeprops={'linewidth':0.3},labeldistance=1.2, textprops={'fontsize':'small'})

	plt.subplot(323)
	data['weeklyMileage'] /= 1000
	# print(np.mean(data['weeklyMileage']),np.std(data['weeklyMileage']))
	data['weeklyMileage'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Weekly mileage (km)')
	plt.grid(linestyle='dotted', linewidth=1)
	# print(np.mean(data['numRaces']),np.std(data['numRaces']))

	plt.subplot(324)
	# print(np.mean(data['avgElevGain']),np.std(data['avgElevGain']))
	data['avgElevGain'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Elevation gain (m)')
	# plt.xlabel('Athletes')
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(325)
	data['avgTrainPace'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Training pace (m/s)')
	plt.grid(linestyle='dotted', linewidth=1)

	plt.subplot(326)
	# print(np.mean(data['numRaces']),np.std(data['numRaces']))
	data['numRaces'].plot.bar()
	plt.xticks(data.index, data.name, fontsize=8)
	plt.ylabel('Number of races')
	plt.grid(linestyle='dotted', linewidth=1)
	# plt.xlabel('Athletes')


	# plt.tight_layout()
	plt.show()


	

	






plotData()
