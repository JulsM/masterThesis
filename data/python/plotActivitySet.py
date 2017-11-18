import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D



def plotSet():
	data = pd.read_csv('../output/activitieSetRelation.csv')

	colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']
	
	
	fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	ax = fig.add_subplot(111, projection='3d')

	
	
	ax.scatter(data['distance'], data['elevation'], data['time'], c='k', s=5)


	plt.legend(fontsize="small", loc="upper right")
	ax.set_xlabel('distance')
	ax.set_ylabel('elevation')
	ax.set_zlabel('time')
	plt.grid()

	print(len(data))
	

	plt.show()

plotSet()
