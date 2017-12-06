import numpy as np
import tensorflow as tf
import itertools
import pandas as pd
import matplotlib.pyplot as plt 
from sklearn import preprocessing
import math as math
import os, shutil
tf.logging.set_verbosity(tf.logging.ERROR)


class RaceTimePredictor:
	
	def __init__(self, FLAGS={}):


		self.FLAGS = {'learning_rate': 0.01,
						'training_steps': 40000,
						'batch_size': 128,
						'model_path': "tf_checkpoints/",
						'data_path' : 'kmeans',
						'athlete' : 'Julian Maurer'}
		for key in FLAGS:
			self.FLAGS[key] = FLAGS[key]

		self.pathDict = {'races' : "../output/"+self.FLAGS['athlete']+"/raceFeatures.csv",
					'kmeans': "../output/"+self.FLAGS['athlete']+"/trainFeatures.csv",
					'all': "../output/"+self.FLAGS['athlete']+"/activitiesFeatures.csv",
					'set' : "../output/"+self.FLAGS['athlete']+"/activitySetFeatures.csv",
					'pred' : "../output/"+self.FLAGS['athlete']+"/predictions.csv"}
		
		self.FEATURE_PATH = self.pathDict[self.FLAGS['data_path']]
		self.PRED_PATH = self.pathDict['pred']
		self.COLUMNS = ["dist", "elev", "hilly", "cs", "atl", "ctl", "isRace", "avgVo2max", "time", "avgTrainPace", "gender"]
		
		self.FEATURES = ["dist", "elev", "hilly", "cs", "atl", "ctl", "isRace", "avgVo2max"]

		self.LABEL = "time"

		self.training_set = None
		self.test_set = None


		

		

	

	def plotStatistics(self):
		training_set, test_set, prediction_set = loadData()
		# training_set, test_set, prediction_set = normalize(training_set, test_set, prediction_set)
		dataset = pd.concat([training_set, test_set])
		# training_set.hist()

		# dataset.plot(kind='density', subplots=True, layout=(3,3), sharex=False)

		# pd.plotting.scatter_matrix(dataset)

		cax = plt.matshow(dataset.corr(), vmin=-1, vmax=1)
		plt.colorbar(cax)
		locs, labs = plt.xticks()
		plt.xticks(np.arange(len(COLUMNS)), COLUMNS)
		plt.yticks(np.arange(len(COLUMNS)), COLUMNS)

		# plt.scatter(dataset['dist'], dataset['CS'])
		plt.show()

	def clearOldFiles(self):
		if(os.path.isdir(self.FLAGS['model_path']+self.FLAGS['athlete'])):
			filelist = [ f for f in os.listdir(self.FLAGS['model_path']+self.FLAGS['athlete']+'/')]
			for f in filelist:
				shutil.rmtree(os.path.join(self.FLAGS['model_path']+self.FLAGS['athlete']+'/', f))
		# if tf.gfile.Exists(self.FLAGS['model_path']):
	 #   		tf.gfile.DeleteRecursively(self.FLAGS['model_path']) 

	def normalize(self, data):
		# mean, std = train[FEATURES].mean(axis=0), train[FEATURES].std(axis=0, ddof=0)
		
		data[self.FEATURES] = self.std_scaler.transform(data[self.FEATURES])
		# print(data)
		return data


	
	def loadTrainData(self):
		train_data = pd.read_csv(self.FEATURE_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		train_data = pd.DataFrame(train_data, columns=self.COLUMNS)
		# train_data = train_data.sample(frac=1).reset_index(drop=True)
		return train_data

	def splitKFold(self, k, i):
		# print(self.train_data)
		races = self.train_data[self.train_data.isRace == 1]
		noRaces = pd.DataFrame(self.train_data[self.train_data.isRace == -1], columns=self.COLUMNS)
		foldLen = int(len(races) / k)
		start = i * foldLen
		if i + 1 == k:
			end = len(races)
		else:
			end = (i + 1) * foldLen
		# print('len races: ', len(races))
		# print(start, end)
		# print(races)
		test = races[start:end]
		train = races[0:start]
		train = train.append(races[end:])
		
		self.test_set = pd.DataFrame(test, columns=self.COLUMNS).reset_index(drop=True)
		self.training_set = noRaces.append(train).reset_index(drop=True)
		# print(self.training_set, self.test_set)
		



	def get_input_fn(self, data_set, num_epochs=None, shuffle=True):
			return tf.estimator.inputs.pandas_input_fn(x=pd.DataFrame({k: data_set[k].values for k in self.FEATURES}), 
		  		y = pd.Series(data_set[self.LABEL].values), batch_size=self.FLAGS['batch_size'], num_epochs=num_epochs, shuffle=shuffle)



	def model_fn(self, features, labels, mode, params):
	 

		# Connect the first hidden layer to input layer
		feature_cols = [tf.feature_column.numeric_column(k) for k in self.FEATURES]
		input_layer = tf.feature_column.input_layer(features=features, feature_columns=feature_cols)


		# Connect the first hidden layer to second hidden layer with relu
		hidden_layer = tf.layers.dense(input_layer, 5, activation=tf.nn.elu, 
			kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(scale_l1=1.0, scale_l2=1.0), name='hidden_1')

		h1_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_1')
		tf.summary.histogram('kernel_1', h1_vars[0])
		tf.summary.histogram('bias_1', h1_vars[1])
		tf.summary.histogram('activation_1', hidden_layer)

		if mode == tf.estimator.ModeKeys.TRAIN:
			hidden_layer = tf.layers.dropout(hidden_layer, rate=0.35, name='dropout_1')
			tf.summary.scalar('dropout_1', tf.nn.zero_fraction(hidden_layer))



		# Connect the second hidden layer to first hidden layer with relu
		# hidden_layer = tf.layers.dense(hidden_layer, 5, activation=tf.nn.elu, 
		# 	kernel_regularizer=tf.contrib.layers.l1_l2_regularizer(scale_l1=1.0, scale_l2=1.0), name='hidden_2')

		# h2_vars = tf.get_collection(tf.GraphKeys.TRAINABLE_VARIABLES, 'hidden_2')
		# tf.summary.histogram('kernel_2', h2_vars[0])
		# tf.summary.histogram('bias_2', h2_vars[1])
		# tf.summary.histogram('activation_2', hidden_layer)

		# if mode == tf.estimator.ModeKeys.TRAIN:
		# 	hidden_layer = tf.layers.dropout(hidden_layer, rate=0.35, name='dropout_2')
		# 	tf.summary.scalar('dropout_2', tf.nn.zero_fraction(hidden_layer))



		# Connect the output layer to second hidden layer (no activation fn)
		output_layer = tf.layers.dense(hidden_layer, 1, name='output')

		# Reshape output layer to 1-dim Tensor to return predictions
		predictions = tf.reshape(output_layer, [-1])

		# Provide an estimator spec for `ModeKeys.PREDICT`.
		if mode == tf.estimator.ModeKeys.PREDICT:
			return tf.estimator.EstimatorSpec(mode=mode,predictions={self.LABEL: predictions})


		# Calculate loss using mean squared error
		loss = tf.losses.mean_squared_error(labels, predictions)

		reg_losses = tf.get_collection(tf.GraphKeys.REGULARIZATION_LOSSES)
		loss = tf.add_n([loss] + reg_losses)

		
		tf.summary.scalar("reg_loss", reg_losses[0])
		tf.summary.scalar("train_error", loss)



		optimizer = tf.train.AdamOptimizer(learning_rate=params["learning_rate"])
		train_op = optimizer.minimize(loss=loss, global_step=tf.train.get_global_step())

		alpha_t = optimizer._lr * tf.sqrt(1-optimizer._beta2_power) / (1-optimizer._beta1_power)
		tf.summary.scalar("learning_rate", alpha_t)
		
		

		# Calculate root mean squared error as additional eval metric
		eval_metric_ops = {
		  "rmse": tf.metrics.root_mean_squared_error(tf.cast(labels, tf.float64), tf.cast(predictions, tf.float64)),
		  "accuracy" : tf.metrics.mean(1 - tf.divide(tf.abs(tf.cast(labels, tf.float64) - tf.cast(predictions, tf.float64)), tf.cast(labels, tf.float64)))
		  # "r" : tf.contrib.metrics.streaming_pearson_correlation(tf.cast(predictions, tf.float32), tf.cast(labels, tf.float32))
		}
		
		# Provide an estimator spec for `ModeKeys.EVAL` and `ModeKeys.TRAIN` modes.
		return tf.estimator.EstimatorSpec(mode=mode, loss=loss, train_op=train_op, eval_metric_ops=eval_metric_ops)


		return EstimatorSpec(mode, predictions, loss, train_op, eval_metric_ops)


	def trainPredictor(self):
		train_input_fn = self.get_input_fn(self.training_set, num_epochs=None, shuffle=True)

		# Train
		self.estimator.train(input_fn=train_input_fn, steps=self.FLAGS['training_steps'])


	def evaluatePredictor(self):
		# Score accuracy
		train_input_fn = self.get_input_fn(self.training_set, num_epochs=1, shuffle=False)
		ev = self.estimator.evaluate(input_fn=train_input_fn)
		print('Train set evaluation')
		print("Mean Squared Error: %s min" % ev['loss'])
		print("Root Mean Squared Error: %s min\n" % ev["rmse"])
		test_input_fn = self.get_input_fn(self.test_set, num_epochs=1, shuffle=False)
		ev = self.estimator.evaluate(input_fn=test_input_fn)
		print('Test set evaluation')
		print("Mean Squared Error: %s min" % ev['loss'])
		print("Root Mean Squared Error: %s min" % ev["rmse"])
		print("Accuracy: {0} % \n".format(round(ev['accuracy'] * 100, 2)))
		# print("R: %s" % ev["r"])
		return ev


	def predictTimes(self):
		print('\nPredictions\n')
		pred_data = pd.read_csv(self.PRED_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		prediction_set = pd.DataFrame(pred_data, columns=self.COLUMNS)
		f = list(self.FEATURES)
		f.append(self.LABEL)
		print(prediction_set[f])
		# prediction_set = pd.DataFrame([(10000,7,0,0,17,49.29,1,37.98,36.5,3.0034449760766,1)], columns=self.COLUMNS)
		# prediction_set = pd.DataFrame([(10000,20,0,0,47.3,49.57,1,43.21,36.5,3.452075862069,1)], columns=self.COLUMNS)
		prediction_set = self.normalize(prediction_set)
		# Print out predictions
		predict_input_fn = self.get_input_fn(prediction_set, num_epochs=1, shuffle=False)
		predictions = self.estimator.predict(input_fn=predict_input_fn)
		pred = list()
		
		for i, p in enumerate(predictions):
			print("Predicted time %s: %s min" % (i, round(p[self.LABEL], 2)))
			pred.append(p[self.LABEL])
			accuracy = round((1 - (abs(p[self.LABEL] - prediction_set[self.LABEL][i]) / prediction_set[self.LABEL][i])) * 100, 2)
			print("+/- Seconds: %s, Accuracy: %s %%" % (round((p[self.LABEL] - prediction_set[self.LABEL][i]) * 60, 2), accuracy))

	


	def trainCrossValidated(self, kfold):
		print('Load Data')
		self.train_data = self.loadTrainData()
		self.clearOldFiles()
		kfoldLosses = []
		kfoldRmse = []
		kfoldAccuracy = []
		print('Start training\n')
		for i in range(kfold):
			print('Iteration ', i+1)
			model_params = {"learning_rate": self.FLAGS['learning_rate']}
			self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp_'+str(i))

			self.splitKFold(kfold, i)
			print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))
			for index, row in self.test_set.iterrows():
				print('distance: %s, elevation: %s, time: %s' % (row['dist'], row['elev'], row['time']))
			self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
			self.training_set = self.normalize(self.training_set)
			self.test_set = self.normalize(self.test_set)
			
			self.trainPredictor()
			metrics = self.evaluatePredictor()
			kfoldLosses.append(metrics['loss'])
			kfoldRmse.append(metrics['rmse'])
			kfoldAccuracy.append(metrics['accuracy'])

		print("\nMean MSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldLosses)))
		print("Mean RMSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldRmse)))
		print("Mean Accuracy for {.2}-fold cross validation: {.2} %".format(kfold, np.mean(kfoldAccuracy) * 100))
		# self.predictTimes()


	def trainStandard(self):
		print('Load Data')
		self.training_set = self.loadTrainData()
		self.clearOldFiles()

		model_params = {"learning_rate": self.FLAGS['learning_rate']}		
		self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp')
		
		self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
		self.training_set = self.normalize(self.training_set)

		test_data = pd.read_csv(self.PRED_PATH, skipinitialspace=True, skiprows=1, names=self.COLUMNS)
		test_set = pd.DataFrame(test_data, columns=self.COLUMNS)
		self.test_set = self.normalize(test_set)
		print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))

		print('Start training\n')
		self.trainPredictor()

		self.evaluatePredictor()

		self.predictTimes()

	def trainWithPretraining(self, kfold):
		print('Pre-train Set')
		self.FEATURES = ["dist", "elev", "hilly", "cs", "atl", "ctl", "isRace", "avgVo2max", "avgTrainPace", "gender"]
		self.FEATURE_PATH = self.pathDict['set']
		self.training_set = self.loadTrainData()
		print('train size: ',len(self.training_set))
		self.clearOldFiles()
		model_params = {"learning_rate": self.FLAGS['learning_rate']}
		self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/pretrain')
		self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
		self.training_set = self.normalize(self.training_set)
		
		self.trainPredictor()

		print('Train kmeans\n')
		# self.FLAGS['training_steps'] = 10000
		# self.FEATURE_PATH = self.pathDict['kmeans']
		# self.training_set = self.loadTrainData()
		# print('train size: ',len(self.training_set))
		# self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
		# self.training_set = self.normalize(self.training_set)
		# print('Start training\n')
		# self.trainPredictor()

		### copy pre-trained model to kfold folders
		for i in range(kfold):
			src = self.FLAGS['model_path']+self.FLAGS['athlete']+'/pretrain'
			dst = self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp_'+str(i)
			shutil.copytree(src, dst)

		
		# print('Train races\n')
		# self.FLAGS['training_steps'] = 5000
		self.FEATURE_PATH = self.pathDict['kmeans']
		self.train_data = self.loadTrainData()
		kfoldLosses = []
		kfoldRmse = []
		kfoldAccuracy = []
		for i in range(kfold):
			print('Iteration ', i+1)
			model_params = {"learning_rate": self.FLAGS['learning_rate']}
			self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp_'+str(i))

			self.splitKFold(kfold, i)
			print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))
			for index, row in self.test_set.iterrows():
				print('distance: %s, elevation: %s, time: %s' % (row['dist'], row['elev'], row['time']))
			self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
			self.training_set = self.normalize(self.training_set)
			self.test_set = self.normalize(self.test_set)
			
			self.trainPredictor()
			metrics = self.evaluatePredictor()
			kfoldLosses.append(metrics['loss'])
			kfoldRmse.append(metrics['rmse'])
			kfoldAccuracy.append(metrics['accuracy'])

		print("\nMean MSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldLosses)))
		print("Mean RMSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldRmse)))
		print("Mean Accuracy for {.2}-fold cross validation: {.2} %".format(kfold, np.mean(kfoldAccuracy) * 100))
		# self.predictTimes()

	def predictOnly(self):
		model_params = {"learning_rate": self.FLAGS['learning_rate']}
		self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp')
		self.predictTimes()


	def crossValidateAll(self, dictAthletes):
		athleteLosses = []
		athleteRmse = []
		athleteAccuracy = []
		for athlete, kfold in dictAthletes.items():

			self.FLAGS['athlete'] = athlete
			self.train_data = self.loadTrainData()
			self.clearOldFiles()
			kfoldLosses = []
			kfoldRmse = []
			kfoldAccuracy = []
			print('Athlete ', athlete)
			for i in range(kfold):
				print('Iteration ', i+1)
				model_params = {"learning_rate": self.FLAGS['learning_rate']}
				self.estimator = tf.estimator.Estimator(model_fn=self.model_fn, params=model_params, model_dir=self.FLAGS['model_path']+self.FLAGS['athlete']+'/temp_'+str(i))

				self.splitKFold(kfold, i)
				print('train size: ',len(self.training_set), ' test size: ', len(self.test_set))
				for index, row in self.test_set.iterrows():
					print('distance: %s, elevation: %s, time: %s' % (row['dist'], row['elev'], row['time']))
				self.std_scaler = preprocessing.StandardScaler().fit(self.training_set[self.FEATURES])
				self.training_set = self.normalize(self.training_set)
				self.test_set = self.normalize(self.test_set)
				
				self.trainPredictor()
				metrics = self.evaluatePredictor()
				kfoldLosses.append(metrics['loss'])
				kfoldRmse.append(metrics['rmse'])
				kfoldAccuracy.append(metrics['accuracy'])

			print("\nMean MSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldLosses)))
			print("Mean RMSE for {.2}-fold cross validation: {.2} min".format(kfold, np.mean(kfoldRmse)))
			print("Mean Accuracy for {.2}-fold cross validation: {.2} %".format(kfold, np.mean(kfoldAccuracy) * 100))

			athleteLosses.append(np.mean(kfoldLosses))
			athleteRmse.append(np.mean(kfoldRmse))
			athleteAccuracy.append(np.mean(kfoldAccuracy))


		print("\nMean MSE over all athletes with cross validation: {.2} min".format(np.mean(athleteLosses)))
		print("Mean RMSE over all athletes with cross validation: {.2} min".format(np.mean(athleteRmse)))
		print("Mean Accuracy over all athletes with cross validation: {.2} %".format(np.mean(athleteAccuracy) * 100))


		
def main(unused_argv):
	predictor = RaceTimePredictor({'training_steps': 40000, 'data_path' : 'kmeans', 'athlete' : 'Julian Maurer'})
	# predictor.trainCrossValidated(4)
	# predictor.trainStandard()
	# predictor.trainWithPretraining(4)
	# predictor.predictOnly()

	athleteDict = {'Julian Maurer' : 4,
					'Florian Daiber' : 2}
	predictor.crossValidateAll(athleteDict)

if __name__ == "__main__":
	tf.app.run()