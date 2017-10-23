from __future__ import absolute_import
from __future__ import division
from __future__ import print_function

import tensorflow as tf

import numpy as np

import matplotlib.pyplot as plt
from sklearn.model_selection import train_test_split

# Generate Range Data
num_of_examples = 1000

X_tr = np.random.uniform(low=[0.0, 0], high=[45, 90], size=[num_of_examples, 2])
formula = [np.power(X_tr[:, 0], 2) * np.sin(np.deg2rad(2 * X_tr[:, 1])) / 9.8]
Y_tr = np.reshape(formula, (num_of_examples, 1))

X_train, X_test, Y_train, Y_test = train_test_split(X_tr, Y_tr, test_size=0.2, random_state=42)

total_len = X_train.shape[0]

# Parameters
learning_rate = 0.001
training_epochs = 500
batch_size = 10
display_step = 1
dropout_rate = 0.9

# Network Parameters
n_hidden_1 = 32 # 1st layer number of features
n_hidden_2 = 200 # 2nd layer number of features
n_hidden_3 = 200
n_hidden_4 = 256
n_input = X_train.shape[1]
n_classes = 1

# tf Graph input

x = tf.placeholder("float", [None, 2])
y = tf.placeholder("float", [None, 1])


# Create model
def model(x, weights, biases):
    with tf.name_scope("dnn"):
        layer_1 = tf.add(tf.matmul(x, weights['h1']), biases['b1'])
        layer_1 = tf.nn.relu(layer_1)
        layer_2 = tf.add(tf.matmul(layer_1, weights['h2']), biases['b2'])
        layer_2 = tf.nn.relu(layer_2)
        layer_3 = tf.add(tf.matmul(layer_2, weights['h3']), biases['b3'])
        layer_3 = tf.nn.relu(layer_3)
        layer_4 = tf.add(tf.matmul(layer_3, weights['h4']), biases['b4'])
        layer_4 = tf.nn.relu(layer_4)
        out_layer = tf.matmul(layer_4, weights['out']) + biases['out']
        return out_layer

# Store layers weight & bias
with tf.name_scope("weights-biases"):
    weights = {
        'h1': tf.Variable(tf.random_normal([n_input, n_hidden_1], 0, 0.1)),
        'h2': tf.Variable(tf.random_normal([n_hidden_1, n_hidden_2], 0, 0.1)),
        'h3': tf.Variable(tf.random_normal([n_hidden_2, n_hidden_3], 0, 0.1)),
        'h4': tf.Variable(tf.random_normal([n_hidden_3, n_hidden_4], 0, 0.1)),
        'out': tf.Variable(tf.random_normal([n_hidden_4, n_classes], 0, 0.1))
    }
    biases = {
        'b1': tf.Variable(tf.random_normal([n_hidden_1], 0, 0.1)),
        'b2': tf.Variable(tf.random_normal([n_hidden_2], 0, 0.1)),
        'b3': tf.Variable(tf.random_normal([n_hidden_3], 0, 0.1)),
        'b4': tf.Variable(tf.random_normal([n_hidden_4], 0, 0.1)),
        'out': tf.Variable(tf.random_normal([n_classes], 0, 0.1))
    }

# Construct model
with tf.name_scope("train"):
    y_pred = model(x, weights, biases)
    cost = tf.reduce_mean(tf.square(y_pred-y))
    optimizer = tf.train.AdamOptimizer(learning_rate=learning_rate).minimize(cost)

# Launch the graph
with tf.Session() as sess:
    sess.run(tf.initialize_all_variables())

    # Training cycle
    for epoch in range(training_epochs):
        avg_cost = 0.
        total_batch = int(total_len/batch_size)
        # Loop over all batches
        for i in range(total_batch-1):
            batch_x = X_train[i*batch_size:(i+1)*batch_size]
            batch_y = Y_train[i*batch_size:(i+1)*batch_size]
            # Run optimization op (backprop) and cost op (to get loss value)
            _, c, p = sess.run([optimizer, cost, y_pred], feed_dict={x: batch_x,
                                                          y: batch_y})
            # Compute average loss
            avg_cost += c / total_batch

        # sample prediction
        label_value = batch_y
        estimate = p
        err = label_value-estimate
        print ("num batch:", total_batch)

        # Display logs per epoch step
        if epoch % display_step == 0:
            print ("Epoch:", '%04d' % (epoch+1), "cost=", "{:.9f}".format(avg_cost))
            print ("[*]----------------------------")
            for i in xrange(10):
                print ("label value:", label_value[i], "estimated value:", estimate[i])
            print ("[*]============================")

    # Test model
    accuracy = sess.run(cost, feed_dict={x: X_test, y: Y_test})
    predicted_vals = sess.run(y_pred, feed_dict={x: X_test})
    print ("Accuracy:", accuracy)

plot_delta(Y_test=Y_test, Y_pred=predicted_vals)

def plot_delta(Y_test, Y_pred):
    x = np.linspace(0, 1, 200)
    fig, ax = plt.subplots()
    y = Y_test - Y_pred
    ax.fill(x, y, zorder=10)
    ax.grid(True, zorder=5)
    plt.show()