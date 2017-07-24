import tensorflow as tf
import numpy as np
import matplotlib.pyplot as plt


# read data
def readData(cols): 
    data = np.genfromtxt('Julian Maurer_data.csv', delimiter=',', skip_header=1, usecols = cols, dtype=float)
    # print(data)
    
    return data

# normalize data
def normalize(array):
    return (array - array.mean()) / array.std()

# denormalize data
def denormalize(array):
    return array.std() * array + array.mean()

def plotRawData():
    data = np.genfromtxt('Julian Maurer_data.csv', delimiter=',', skip_header=1)

    time=data[:, :1].flatten()
    dist=data[:, 1:2].flatten()
    pace=data[:, 2:3].flatten()
    elevation=data[:, 3:4].flatten()
    vo2max=data[:, 4:5].flatten()
    plt.subplot(4, 1, 1)
    plt.plot(dist, time, linestyle = 'None', marker='+')
    plt.xlabel('distance km')
    plt.ylabel('time min')

    plt.subplot(4, 1, 2)
    plt.plot(dist, pace, linestyle = 'None', marker='+')
    plt.xlabel('distance km')
    plt.ylabel('pace')

    plt.subplot(4, 1, 3)
    plt.plot(dist, elevation, linestyle = 'None', marker='+')
    plt.xlabel('distance km')
    plt.ylabel('elevation km')

    plt.subplot(4, 1, 4)
    plt.plot(dist, vo2max, linestyle = 'None', marker='+')
    plt.xlabel('distance km')
    plt.ylabel('VO2max')

    # data_n = normalize(data)
    # time=data_n[:, :1].flatten()
    # dist=data_n[:, 1:2].flatten()
    # pace=data_n[:, 2:3].flatten()
    # elevation=data_n[:, 3:4].flatten()
    # plt.subplot(3, 2, 2)
    # plt.plot(dist, time, linestyle = 'None', marker='+')
    # plt.xlabel('distance km')
    # plt.ylabel('time min')

    # plt.subplot(3, 2, 4)
    # plt.plot(dist, pace, linestyle = 'None', marker='+')
    # plt.xlabel('distance km')
    # plt.ylabel('pace')

    # plt.subplot(3, 2, 6)
    # plt.plot(dist, elevation, linestyle = 'None', marker='+')
    # plt.xlabel('distance km')
    # plt.ylabel('elevation km')
    plt.show()


# multiple linear regression

def regression(train_X, train_Y, data): 

    # Parameters
    learning_rate = 0.01
    # learning_rate = 0.99
    training_epochs = 5000
    display_step = 100

    org_X = data[:, 1:]
    org_Y = data[:, :1]

    n_features = train_X.shape[1]
    n_samples = train_X.shape[0]

    print('n features: ', n_features)
    print('n samples: ', n_samples)

    # tf Graph Input
    X = tf.placeholder(tf.float32, [None, n_features])
    Y = tf.placeholder(tf.float32, [None, 1])

    # Set model weights
    W = tf.Variable(tf.random_normal([n_features, 1], stddev=0.01))
    b = tf.Variable(tf.ones([1]))

    # Construct a linear model
    pred = tf.add(tf.matmul(X, W), b)

    # Mean squared error
    cost = tf.reduce_sum(tf.pow(pred-Y, 2))/(2*n_samples)
    # cost = tf.reduce_mean(tf.square(pred-Y))
    # cost = tf.reduce_mean(tf.nn.softmax_cross_entropy_with_logits(pred, Y))

    # Gradient descent
    optimizer = tf.train.GradientDescentOptimizer(learning_rate).minimize(cost)

    # Initializing the variables
    init = tf.global_variables_initializer()

    # Launch the graph
    with tf.Session() as sess:
        sess.run(init)

        # print(pred.eval({X: train_X}))
        cost_hist = [];

        # Fit all training data
        for epoch in range(training_epochs):
            sess.run(optimizer, feed_dict={X: train_X, Y: train_Y})
            c = sess.run(cost, feed_dict={X: train_X, Y:train_Y})
            # print(cost_hist)
            cost_hist.append(c)
            # Display logs per epoch step
            if (epoch+1) % display_step == 0:
                
                print("Epoch:", '%04d' % (epoch+1), "cost=", "{:.9f}".format(c))
                    # "W=", sess.run(W), "b=", sess.run(b))

        print("Optimization Finished!")

        # R squared
        predicted_Y = sess.run(pred, feed_dict={X: train_X})

        squared_error = tf.reduce_sum(tf.square(tf.sub(train_Y, predicted_Y)))
        squared_error_mean = tf.reduce_sum(tf.square(tf.sub(train_Y, tf.reduce_mean(train_Y))))
        
        R_squared = 1.0 - tf.div(squared_error, squared_error_mean)
        print(" R squared: ", R_squared.eval())

        # p-value

        # p_val = tf.contrib.metrics.streaming_pearson_correlation(tf.cast(pred, tf.float32), tf.cast(Y, tf.float32))
        # p = sess.run(p_val, feed_dict={X: train_X, Y: train_Y})

        # print(p)
        
        print("Training cost=", c, "W=", sess.run(W),'\n', "b=", sess.run(b), '\n')
        

        # Graphic display
        plt.subplot(2, 1, 1)
        # plt.plot(train_X[:, :1], train_Y, 'ro', label='Original data')
        plt.plot(org_X[:, :1], org_Y, 'ro', label='Original data')
        # p_y = tf.add(tf.matmul(org_X, tf.cast(sess.run(W), tf.float64)), tf.cast(sess.run(b), tf.float64))
        predicted_Y = sess.run(pred, feed_dict={X: org_X})

        joined = np.concatenate((org_X[:, :1], predicted_Y), axis=1)
        joined = joined[joined[:, 0].argsort()]
        plt.plot(joined[:, :1], joined[:, 1:2] , label='Fitted line')
        plt.xlabel('distance km')
        plt.ylabel('time min')
        # plt.legend()

        plt.subplot(2, 1, 2)
        plt.plot(cost_hist)
        plt.xlabel('epochs')
        plt.ylabel('cost')
       


        # test_data = np.asarray([21.09, 256.09, 0.086, 50])
        # test_data = np.asarray([12.8, 218.8, 0.177, 52.52])
        # test_data = np.asarray([11.28, 205.46, 0.15, 47.27])
        # test_data = np.asarray([11.28, 0.15, 47.27])
        # test_data = np.asarray([12.9, 0.248, 45.72])
        test_data = np.asarray([10, 0.058, 57.76])

        
     
        result = np.dot(test_data, sess.run(W))+ sess.run(b)
        print('testergebnis: ', result)

        plt.show()

      


data = readData((0, 1, 3, 4))
norm_data = normalize(data)
train_X = norm_data[:, 1:]
train_Y = norm_data[:, :1]
# plotRawData()
regression(train_X, train_Y, data)