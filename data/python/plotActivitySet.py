import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D
from sklearn import preprocessing



def plotSet():
	data = pd.read_csv('../output/activitieSetRelation.csv')
	print(len(data))
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

	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	for i in range(8, 60):
		filtered = data[(data.distance > i * 500 - 200) & (data.distance < i * 500 + 200)]
		std_scale = preprocessing.StandardScaler().fit(filtered)
		filtered = std_scale.transform(filtered)
		plt.scatter(filtered[:, 1], filtered[:, 2], c='grey', s=2, label='')
	plt.xlabel('elevation')
	plt.ylabel('time')
	plt.legend()
	plt.grid()
	plt.show()

plotSet()
