import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 


def plotElevation():
	data = np.genfromtxt('elevation.csv', delimiter=',', skip_header=1)

	# print(data)
	perc=data[:, :1]
	grade=data[:, 1:2]
	elev=data[:, 2:] / 10

	perc= perc[:250]
	grade = grade[:250]
	elev = elev[:250]

	plt.plot(perc, label="percent")
	# plt.plot(grade, label="grade")
	plt.plot(elev, label="elevation")
	# plt.legend()


	plt.show()


def plotDifferenceElev():
	data = np.genfromtxt('../output/stravaGoogleElevation.csv', delimiter=',', skip_header=1)

	# print(data)
	strava=data[:, :1]
	google=data[:, 1:2]
	diff = abs(strava-google)

	plt.subplot(2, 1, 1)
	plt.plot(strava, label="strava")
	plt.plot(google, label="google")
	plt.legend()
	plt.subplot(2, 1, 2)
	y_pos = np.arange(len(diff))
	plt.bar(y_pos, diff)
	plt.xlabel('difference')
	

	plt.show()


def plotCleanedupElev():
	cleanData = np.genfromtxt('../output/cleanedData.csv', delimiter=',')
	xtremeData = np.genfromtxt('../output/elevProfile.csv', delimiter=',')

	plt.subplot(3, 1, 1)
	plt.plot(cleanData[:, 1:], cleanData[:, :1], label="cleaned up")
	plt.xlabel('cleaned')
	plt.subplot(3, 1, 2)
	result = rdp(cleanData, epsilon=2.5)
	plt.plot(result[:, 1:], result[:, :1], label="cleaned up")
	plt.xlabel('RDP')
	plt.subplot(3, 1, 3)
	plt.plot(xtremeData[:, :1], xtremeData[:, 1:], label="xtreme")
	plt.xlabel('extrema')

	print(len(cleanData), len(result))


	plt.show()

# plotCleanedupElev()
plotDifferenceElev()
