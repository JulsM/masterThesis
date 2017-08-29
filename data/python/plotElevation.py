import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 



def plotDifferenceElev():
	data = np.genfromtxt('../output/stravaGoogleDifference.csv', delimiter=',', skip_header=1)

	# print(data)
	strava=data[:, :1]
	google=data[:, 1:2]
	dist=data[:, 2:3]
	diff = abs(strava-google)

	plt.subplot(2, 1, 1)
	plt.plot(dist, strava, label="strava")
	plt.plot(dist, google, label="google")
	plt.legend()
	plt.subplot(2, 1, 2)
	y_pos = np.arange(len(diff))
	plt.bar(y_pos, diff)
	plt.xlabel('difference')
	

	plt.show()


def plotCleanedupElev():
	cleanData = np.genfromtxt('../output/originalData.csv', delimiter=',')
	segmentData = np.genfromtxt('../output/segments.csv', delimiter=',')
	filteredSegmentData = np.genfromtxt('../output/filteredSegments.csv', delimiter=',')
	recompSegmentData = np.genfromtxt('../output/recomputedSegments.csv', delimiter=',')

	plt.subplot(3, 1, 1)
	plt.plot(cleanData[:, 1:], cleanData[:, :1], 'g', label="cleaned")
	# plt.subplot(3, 1, 2)
	result = rdp(cleanData, epsilon=2.5)
	plt.plot(result[:, 1:], result[:, :1], 'r', label="rdp")
	plt.legend(fontsize="small", loc="lower center")
	plt.subplot(3, 1, 2)
	plt.plot(segmentData[:, :1], segmentData[:, 1:], 'g', label="segment")
	plt.plot(filteredSegmentData[:, :1], filteredSegmentData[:, 1:], 'r', label="filtered")
	plt.legend(fontsize="small", loc="lower center")
	plt.subplot(3, 1, 3)
	plt.plot(recompSegmentData[:, :1], recompSegmentData[:, 1:], 'g', label="recomputed")
	plt.legend(fontsize="small", loc="lower center")

	# print(len(cleanData), len(result))


	plt.show()

plotCleanedupElev()
# plotDifferenceElev()
