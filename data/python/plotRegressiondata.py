import numpy as np
import matplotlib.pyplot as plt 

data = np.genfromtxt('file.csv', delimiter=',', skip_header=1)

data = data[data[:, 1].argsort()]
# print(data)
time=data[:, :1].flatten()
dist=data[:, 1:2].flatten()
pace=data[:, 2:3].flatten()
elevation=data[:, 3:4].flatten()

# dist /= 1000
# time /= 60
# elevation /= 1000

plt.subplot(3, 1, 1)
plt.plot(dist, time, linestyle = 'None', marker='+')
plt.xlabel('distance km')
plt.ylabel('time min')

plt.subplot(3, 1, 2)
plt.plot(dist, pace, linestyle = 'None', marker='+')
plt.xlabel('distance km')
plt.ylabel('pace')

plt.subplot(3, 1, 3)
plt.plot(dist, elevation, linestyle = 'None', marker='+')
plt.xlabel('distance km')
plt.ylabel('elevation km')
plt.show()