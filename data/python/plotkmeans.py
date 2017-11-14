import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D




def plotkmeans():
	data = pd.read_csv('../output/kmeans.csv', skiprows=1, names=['dist', 'elev', 'speed', 'type'])
	
	
	fig = plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
	ax = fig.add_subplot(111, projection='3d')

	races = data[data.type == 'race']
	

	f = (data.dist > 9500) & (data.dist < 10500) | (data.dist > 4500) & (data.dist < 5500) | (data.dist > 20000) & (data.dist < 21800)
	filtered = data[f]

	data = data[~f]
	ax.scatter(data['dist'], data['elev'], data['speed'], c='k', s=3)

	
	print(len(data))

	ax.scatter(filtered['dist'], filtered['elev'], filtered['speed'], c='r', s=3)
	ax.scatter(races['dist'], races['elev'], races['speed'], c='g', s=15, marker='+')

	
	
	plt.legend(fontsize="small", loc="upper right")
	ax.set_xlabel('distance')
	ax.set_ylabel('elevation')
	ax.set_zlabel('speed')
	plt.grid()


	plt.show()

plotkmeans()
