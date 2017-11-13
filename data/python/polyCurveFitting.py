import tensorflow as tf
import numpy as np
import matplotlib.pyplot as plt
import pandas as pd


data = pd.read_csv('efficiency_grad.csv', skipinitialspace=True, skiprows=1, names=['eff', 'grad'])
trX = data['grad'].values /100
trY = data['eff'].values

poly_grad = 5 + 1

def fit():
	learning_rate = 0.01
	training_epochs = 10000
	

	X = tf.placeholder("float32")
	Y = tf.placeholder("float32")

	def model(X, w):
	    terms = []
	    for i in range(poly_grad):
	        term = tf.multiply(w[i], tf.pow(X, i))
	        terms.append(term)
	    return tf.add_n(terms)



	W = tf.Variable([0.] * poly_grad, name="parameters")
	y_model = model(X, W)


	loss = tf.losses.mean_squared_error(Y, y_model)
	# regularizer = tf.nn.l2_loss(W)
	# loss = tf.reduce_mean(loss + 0.0001 * regularizer)
	train_op = tf.train.AdamOptimizer(learning_rate=learning_rate).minimize(loss)

	sess = tf.Session()
	init = tf.global_variables_initializer()
	sess.run(init)
	for epoch in range(training_epochs):
	    for (x, y) in zip(trX, trY):
	        sess.run(train_op, feed_dict={X: x, Y: y})
	        # print(x, y)

	    training_cost = sess.run(loss, feed_dict={X: trX, Y: trY})
	    if epoch % 100 == 0:
	    	print('epoch: ', epoch, 'loss: ',training_cost)


	pred = sess.run(y_model, feed_dict={X: 0})
	print('pred: ', pred)
	w_val = sess.run(W)
	print(w_val)
	pd.DataFrame([w_val]).to_csv('weights.csv', index=False, header=False)

	sess.close()
	return w_val

def plot(w):
	plt.scatter(trX, trY,  s=1)
	x = np.linspace(-0.4, 0.4, 250)
	trY2 = 0
	for i in range(poly_grad):
	    trY2 += w[i] * np.power(x, i)
	plt.plot(x, trY2, 'r', linewidth=0.3, label='Strava')
	y_M = list()
	for i in x:
		y_M.append(getMinettiCost(i))

	plt.plot(x, y_M, 'b', linewidth=0.3, label='Minetti')
	plt.plot(np.array([-0.4, 0.4]), np.array([1, 1]), 'k', linewidth=0.3)
	# plt.xlim(-0.2, 0.05)
	# plt.ylim(-0.5, 1.5)
	plt.legend()
	plt.show()

def getMinettiCost(g):
	return (155.4 * pow(g, 5)  - 30.4 * pow(g, 4) - 43.3 * pow(g, 3) + 46.3 * pow(g, 2) + (19.5 * g) + 3.6) / 3.6


# w = np.array([0.98323727, 2.82190776, 19.18093681, 4.0301404, -46.39165978, -43.2396965])
# plot(w)
w = fit()
plot(w)