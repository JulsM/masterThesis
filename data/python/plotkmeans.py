import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D



def plotkmeans():
	data = pd.read_csv('../output/kmeans.csv')

	colors = ['red', 'blue', 'green', 'yellow', 'cyan', 'grey', 'purple', 'coral', 'k', 'saddlebrown']
	
	
	fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	ax = fig.add_subplot(111, projection='3d')

	numClusters = data.cluster.nunique();
	
	for c in range(numClusters):
		cluster = data[data.cluster == c]
		ax.scatter(cluster['distance'], cluster['elevation'], cluster['ngp'], c=colors[c], s=5)


	plt.legend(fontsize="small", loc="upper right")
	ax.set_xlabel('distance')
	ax.set_ylabel('elevation')
	ax.set_zlabel('ngp')
	plt.grid()

	print(len(data[data.cluster == 5]))
	print(len(data))
	
	# fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# ax = fig.add_subplot(111, projection='3d')


	# cluster = data[data.cluster == 0]
	# threshold = (cluster.speed.mean() + cluster.speed.max()) / 2
	# group = cluster[cluster.speed > threshold]
	# print(threshold)
	# ax.scatter(group['distance'], group['elevation'], group['speed'], c='k', s=5)
	# group = cluster[cluster.speed < threshold]
	# ax.scatter(group['distance'], group['elevation'], group['speed'], c='r', s=5)
	# print(data[(data.distance > 9500) & (data.distance < 10500)] )

	# rel = data[data.cluster == 4]
	# ax.scatter(rel['distance'], rel['elevation'], rel['time'], c='r', s=5)
	
	
	


	plt.show()

plotkmeans()
