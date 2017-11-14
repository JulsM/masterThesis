import numpy as np
import matplotlib.pyplot as plt 
import pandas as pd

def plotMinetti():
	data = pd.read_csv('../output/minetti.csv', header=None, names=['x', 'y', 'adjusted'])
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
			

	# plt.subplot(1, 1, 1)
	
	plt.plot(data['x'], data['y'] , 'k', label="minetti")
	plt.plot(data['x'], data['adjusted'] , 'r', label="adjusted")
	plt.plot(np.array([-0.3, 0.3]), np.array([1, 1]) , 'g', label="")
	
	plt.legend(fontsize="small", loc="lower center")
	plt.grid()


	plt.show()



def plotTss():
	data = pd.read_csv('../output/tss.csv', header=None, names=['dist', 'alt', 'grade', 'smooth', 'vel', 'gap', 'adjusted'])
	# stravadata = pd.read_csv('../output/stravaNGP.csv', header=None, names=['dist', 'grade', 'ngp', 'vel'])
	
	plt.figure(figsize=(12, 4), dpi=100, facecolor='w')
			

	plt.subplot(1, 1, 1)
	
	# plt.plot(data['dist'], data['alt'] - 270, 'r', label="altitude")
	# plt.plot(data['dist'], data['grade'] * 2, 'k', label="grade")
	# plt.plot(data['dist'], data['smooth'], 'b', label="smoothed")
	# plt.plot(data['dist'], data['vel'] * 4 + 70, 'g', label="velocity")
	plt.plot(data['dist'], data['gap'], 'y', label="gap")
	# plt.plot(data['dist'], data['adjusted'], 'r', label="adjusted")
	plt.plot(np.array([0, 12000]), np.array([1, 1]), 'k', label="")

	# plt.plot(stravadata['dist'], stravadata['grade'], 'k', label="strava grade")
	# plt.plot(stravadata['dist'], stravadata['ngp'], 'r', label="strava ngp")
	# plt.plot(stravadata['dist'], stravadata['vel'], 'g', label="strava vel")
	
	plt.legend(fontsize="small", loc="lower center")
	plt.grid()


	plt.show()

# plotTss()
plotMinetti()
