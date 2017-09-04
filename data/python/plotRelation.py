import numpy as np
import matplotlib.pyplot as plt 
from rdp import rdp 


def plotGradeElevation():
	grade = np.genfromtxt('../output/gradeSmooth.csv', delimiter=',')
	cleanData = np.genfromtxt('../output/originalData.csv', delimiter=',')
	

	plt.subplot(1, 1, 1)
	plt.plot(grade[:, :1], grade[:, 1:]+300, 'g', label="grade")
	plt.plot(cleanData[:, 1:], cleanData[:, :1], 'r', label="elevation")
	plt.legend(fontsize="small", loc="lower center")
	plt.show()

def plotHrVelElev():
	hrVel = np.genfromtxt('../output/hr_velocity.csv', delimiter=',')
	cleanData = np.genfromtxt('../output/originalData.csv', delimiter=',')

	cleanData = rdp(cleanData, epsilon=0.5)
	hrVel = rdp(hrVel, epsilon=1)
	

	plt.subplot(1, 1, 1)
	plt.plot(hrVel[:, :1], hrVel[:, 1:2]+250, 'g', label="hr")
	plt.plot(hrVel[:, :1], hrVel[:, 2:]*5+280, 'b', label="velocity")
	plt.plot(cleanData[:, 1:], cleanData[:, :1], 'r', label="elevation")
	plt.legend(fontsize="small", loc="lower center")
	plt.show()

# plotGradeElevation()
plotHrVelElev()
