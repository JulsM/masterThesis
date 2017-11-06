import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
import matplotlib.pyplot as plt 


tf.logging.set_verbosity(tf.logging.INFO)

# Learning rate for the model
LEARNING_RATE = 0.001

TRAIN_STEPS = 20000

FILE_PATH = "../output/raceFeatures.csv"
SAVE_PATH = "temp"
COLUMNS = ["dist", "elev", "avgSpeed", "hilly", "CS", "time"]
FEATURES = ["dist", "elev", "avgSpeed", "hilly", "CS"]
# FEATURES = ["dist", "elev"]
LABEL = "time"

def plotStatistics():
	training_set, test_set, prediction_set = loadData()
	dataset = pd.concat([training_set, test_set])
	# training_set.hist()

	# dataset.plot(kind='density', subplots=True, layout=(3,3), sharex=False)

	# pd.plotting.scatter_matrix(dataset)

	cax = plt.matshow(dataset.corr(), vmin=-1, vmax=1)
	plt.colorbar(cax)
	locs, labs = plt.xticks()
	plt.xticks(locs[1:-1], COLUMNS)
	plt.yticks(locs[1:-1], COLUMNS)


	# plt.scatter(dataset['dist'], dataset['CS'])


	plt.show()

def clearOldFiles():
	if tf.gfile.Exists(SAVE_PATH):
   		tf.gfile.DeleteRecursively(SAVE_PATH) 

def normalize(train, test):
	label_train = train[LABEL]
	label_test = test[LABEL]
	mean, std = train[FEATURES].mean(axis=0), train[FEATURES].std(axis=0)
	
	train = (train[FEATURES] - mean) /std
	test = (test[FEATURES] - mean) / std
	train = pd.concat([train, label_train], axis=1)
	test = pd.concat([test, label_test], axis=1)
	return train, test


def get_input_fn(data_set, num_epochs=None, shuffle=True):
	return tf.estimator.inputs.pandas_input_fn(x=pd.DataFrame({k: data_set[k].values for k in FEATURES}), 
  		y = pd.Series(data_set[LABEL].values), num_epochs=num_epochs, shuffle=shuffle)


def loadData():
	training_set = pd.read_csv(FILE_PATH, skipinitialspace=True, skiprows=1, names=COLUMNS, nrows=10)
	test_set = pd.read_csv(FILE_PATH, skipinitialspace=True, skiprows=11, names=COLUMNS, nrows=5)
	prediction_set = pd.read_csv(FILE_PATH, skipinitialspace=True, skiprows=16, names=COLUMNS, nrows=1)

	training_set = pd.DataFrame(training_set, columns=COLUMNS)
	test_set = pd.DataFrame(test_set, columns=COLUMNS)
	prediction_set = pd.DataFrame(prediction_set, columns=COLUMNS)
	# prediction_set = pd.DataFrame({"dist": [x for x in range(5, 25, 1)], "time": [x for x in range(5, 25, 1)]})
	return training_set, test_set, prediction_set


def model_fn(features, labels, mode, params):
	# Logic to do the following:
	# 1. Configure the model via TensorFlow operations
	# 2. Define the loss function for training/evaluation
	# 3. Define the training operation/optimizer
	# 4. Generate predictions
	# 5. Return predictions/loss/train_op/eval_metric_ops in EstimatorSpec object
 

	# Connect the first hidden layer to input layer
	feature_cols = [tf.feature_column.numeric_column(k) for k in FEATURES]
	input_layer = tf.feature_column.input_layer(features=features, feature_columns=feature_cols)


	# Connect the first hidden layer to second hidden layer with relu
	hidden_layer = tf.layers.dense(input_layer, 10, activation=tf.nn.relu, kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(), name='hidden_1')

	h1_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_1')
	tf.summary.histogram('kernel', h1_vars[0])
	tf.summary.histogram('bias', h1_vars[1])

	if mode == tf.estimator.ModeKeys.TRAIN:
		hidden_layer = tf.layers.dropout(hidden_layer, rate=0.5, name='dropout')
		tf.summary.scalar('dropout', tf.nn.zero_fraction(hidden_layer))



	# Connect the second hidden layer to first hidden layer with relu
	hidden_layer = tf.layers.dense(hidden_layer, 10, activation=tf.nn.relu, kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(), name='hidden_2')

	h2_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_2')
	tf.summary.histogram('kernel', h2_vars[0])
	tf.summary.histogram('bias', h2_vars[1])

	if mode == tf.estimator.ModeKeys.TRAIN:
		hidden_layer = tf.layers.dropout(hidden_layer, rate=0.25, name='dropout')
		tf.summary.scalar('dropout', tf.nn.zero_fraction(hidden_layer))



	# Connect the output layer to second hidden layer (no activation fn)
	output_layer = tf.layers.dense(hidden_layer, 1, name='output')

	# Reshape output layer to 1-dim Tensor to return predictions
	predictions = tf.reshape(output_layer, [-1])

	# Provide an estimator spec for `ModeKeys.PREDICT`.
	if mode == tf.estimator.ModeKeys.PREDICT:
		return tf.estimator.EstimatorSpec(mode=mode,predictions={LABEL: predictions})


	# Calculate loss using mean squared error
	loss = tf.losses.mean_squared_error(labels, predictions)

	reg_losses = tf.get_collection(tf.GraphKeys.REGULARIZATION_LOSSES)
	loss = tf.add_n([loss] + reg_losses)

	
	tf.summary.scalar("reg_loss", reg_losses[0])
	tf.summary.scalar("train_error", loss)



	optimizer = tf.train.AdamOptimizer(learning_rate=params["learning_rate"])
	train_op = optimizer.minimize(loss=loss, global_step=tf.train.get_global_step())
	# grad= optimizer.compute_gradients(loss)
	# train_op = optimizer.apply_gradients(grad, global_step=tf.train.get_global_step())
	tf.summary.scalar("learning_rate", optimizer._lr)
	
	# for g, v in enumerate(grad):
	# 	tf.summary.scalar("gradient", g)

	# Calculate root mean squared error as additional eval metric
	eval_metric_ops = {
	  "rmse": tf.metrics.root_mean_squared_error(tf.cast(labels, tf.float64), tf.cast(predictions, tf.float64))
	}
	
	# Provide an estimator spec for `ModeKeys.EVAL` and `ModeKeys.TRAIN` modes.
	return tf.estimator.EstimatorSpec(mode=mode, loss=loss, train_op=train_op, eval_metric_ops=eval_metric_ops)


	return EstimatorSpec(mode, predictions, loss, train_op, eval_metric_ops)


def main(unused_argv):

	clearOldFiles()
	training_set, test_set, prediction_set = loadData()
	# training_set, test_set = normalize(training_set, test_set)
	# Set model params
	model_params = {"learning_rate": LEARNING_RATE}

	# Instantiate Estimator
	nn = tf.estimator.Estimator(model_fn=model_fn, params=model_params, model_dir=SAVE_PATH, config=tf.estimator.RunConfig().replace(save_summary_steps=100))

	train_input_fn = get_input_fn(training_set, num_epochs=None, shuffle=True)

	# Train
	nn.train(input_fn=train_input_fn, steps=TRAIN_STEPS)

	# Score accuracy
	test_input_fn = get_input_fn(test_set, num_epochs=1, shuffle=False)
	ev = nn.evaluate(input_fn=test_input_fn)
	print("Loss: %s" % ev["loss"])
	print("Root Mean Squared Error: %s" % ev["rmse"])


	# Print out predictions
	predict_input_fn = get_input_fn(prediction_set, num_epochs=1, shuffle=False)
	predictions = nn.predict(input_fn=predict_input_fn)
	pred = list()
	for i, p in enumerate(predictions):
		print("Prediction %s: %s" % (i + 1, p[LABEL]))
		pred.append(p[LABEL])
		print("Seconds away: %s" % ((prediction_set[LABEL][i] - p[LABEL]) * 60))

	
	# plt.scatter(prediction_set.loc[:,'dist'], pred)
	# plt.scatter(training_set.loc[:,'dist'], training_set.loc[:,'time'])
	# plt.show()



if __name__ == "__main__":
	tf.app.run()
	# plotStatistics()