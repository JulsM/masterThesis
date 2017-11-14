import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D




def plotkmeans():
	data = pd.read_csv('../output/kmeans.csv', skiprows=1, names=['dist', 'elev', 'speed'])
	
	
	fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	ax = fig.add_subplot(111, projection='3d')

	ax.scatter(data['dist'], data['elev'], data['speed'], c='k', s=5)

	data = data[(data.dist > 9500) & (data.dist < 10500) | (data.dist > 4500) & (data.dist < 5500) | (data.dist > 20000) & (data.dist < 21800)]
	print(len(data))

	ax.scatter(data['dist'], data['elev'], data['speed'], c='r')

	
	
	plt.legend(fontsize="small", loc="upper right")
	plt.grid()


	plt.show()

plotkmeans()
