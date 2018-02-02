import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 



def plotDifferenceElev():
	data = np.genfromtxt('../output/Julian Maurer/originalData.csv', delimiter=',', skip_header=1)

	# print(data)
	strava=data[:, :1]
	google=data[:, 1:2]
	dist=data[:, 2:3]
	diff = abs(strava-google)

	# plt.subplot(2, 1, 1)
	plt.plot(dist, strava, label="Strava")
	plt.plot(dist, google, label="Google")
	plt.legend()
	plt.xlabel('distance')
	plt.ylabel('altitude')
	# plt.subplot(2, 1, 2)
	# y_pos = np.arange(len(diff))
	# plt.bar(y_pos, diff)
	# plt.xlabel('difference')
	

	plt.show()


def plotCleanedupElev(athlete):
	cleanData = np.genfromtxt('../output/'+athlete+'/originalData.csv', delimiter=',', skip_header=1)
	rdpData = np.genfromtxt('../output/'+athlete+'/rdp.csv', delimiter=',')
	segmentData = np.genfromtxt('../output/'+athlete+'/segments.csv', delimiter=',')
	filteredSegmentData = np.genfromtxt('../output/'+athlete+'/filteredSegments.csv', delimiter=',')
	recompSegmentData = np.genfromtxt('../output/'+athlete+'/recomputedSegments.csv', delimiter=',')
	climbs = []
	climbs.append([])
	climbs.append([])
	with open('../output/'+athlete+'/climbs.csv') as f:
		lines=f.readlines()
		for line in lines:
			l = np.fromstring(line, dtype=float, sep=',')
			dist = l[::2]
			elev = l[1::2]
			climbs[0].extend(dist)
			climbs[1].extend(elev)
			climbs[0].extend([float('nan')])
			climbs[1].extend([float('nan')])
			

	# plt.subplot(2, 1, 1)
	# plt.plot(cleanData[:, 2:], cleanData[:, 1:2], 'g', label="Original")
	# plt.plot(rdpData[:, :1], rdpData[:, 1:], 'r', label="RDP")
	# plt.legend(fontsize="small", loc="lower center")
	# plt.xlabel('distance')
	# plt.ylabel('altitude')
	# plt.subplot(2, 1, 2)
	# plt.plot(segmentData[:, 0], segmentData[:, 1], 'b', label="Elevation profile")
	# plt.plot(segmentData[:, :1], segmentData[:, 1:], 'g', label="segment")
	# plt.plot(filteredSegmentData[:, :1], filteredSegmentData[:, 1:], 'r', label="filtered")
	# plt.legend(fontsize="small", loc="lower center")
	# plt.xlabel('distance')
	# plt.ylabel('altitude')
	# plt.subplot(3, 1, 3)
	plt.plot(recompSegmentData[:, :1], recompSegmentData[:, 1:], 'b', label="Elevation profile")
	plt.plot(climbs[0], climbs[1], 'r', label="Climbs")
	plt.legend(fontsize="small", loc="lower center")
	plt.xlabel('distance')
	plt.ylabel('altitude')

	plt.tight_layout()
	plt.show()

plotCleanedupElev('Julian Maurer')
# plotDifferenceElev()
