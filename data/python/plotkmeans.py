import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D



def plotkmeans():
	data = pd.read_csv('../output/Martin Muehlhan/kmeans.csv')

	colors = ['orange', 'blue', 'green', 'purple', 'cyan', 'red', 'yellow', 'coral', 'k', 'saddlebrown']
	numClusters = data.cluster.nunique();
	
	# fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	# ax = fig.add_subplot(111, projection='3d')

	# for c in range(numClusters):
	# 	cluster = data[data.cluster == c]
	# 	ax.scatter(cluster['distance'], cluster['elevation'], cluster['ngp'], c=colors[c], s=5)


	# plt.legend(fontsize="small", loc="upper right")
	# ax.set_xlabel('distance')
	# ax.set_ylabel('elevation')
	# ax.set_zlabel('ngp')
	# plt.grid()

	# print(len(data[data.cluster == 5]))
	print(len(data), len(data[data.cluster == 5]))

	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	for c in range(numClusters):
		cluster = data[data.cluster == c]
		plt.scatter(cluster['distance'], cluster['ngp'], c=colors[c], s=5)
	# plt.legend(fontsize="small", loc="upper right")
	plt.xlabel('distance (m)')
	plt.ylabel('normalized graded pace (m/s)')
	plt.grid()


	plt.show()

plotkmeans()
