import mqtt from 'mqtt';
import express from 'express';
import bodyParser from 'body-parser';

const app = express();
const port = 3000;

// Parse incoming JSON requests
app.use(bodyParser.json());

// MQTT Broker Configuration
const brokerUrl = 'mqtt://91.108.102.82'; // Replace with your MQTT broker URL
const options = {
  clientId: `mqtt-js-client-${Math.random().toString(16).substr(2, 8)}`,
  clean: true,
  connectTimeout: 4000,
  username: '', // Add if required
  password: '', // Add if required
};

// Connect to the MQTT broker
const client = mqtt.connect(brokerUrl, options);

client.on('connect', () => {
  console.log('Connected to MQTT broker');
});

client.on('error', (err) => {
  console.error('MQTT Connection error:', err);
});

// Store last messages for topics (in memory)
const lastMessages = {};

// Listen for messages on subscribed topics
client.on('message', (topic, message) => {
  console.log(`Message received on topic ${topic}:`, message.toString());
  lastMessages[topic] = message.toString(); // Store the last message for the topic
  console.log('Last messages:', lastMessages); // Log to see the current state
});

// Listen for the 'suback' event to capture retained messages when subscribing
client.on('suback', (packet) => {
  packet.granted.forEach((qos, index) => {
    const topic = packet.topics[index];
    if (qos > 0) {
      console.log(`Subscribed to topic: ${topic}`);
      // Check if there's a retained message for the topic
      client.publish(topic, '', { qos: 1, retain: true });
    }
  });
});

// API: Publish to a topic
app.post('/publish', (req, res) => {
  const { topic, message, retain } = req.body;
  if (!topic || !message) {
    return res.status(400).json({ error: 'Topic and message are required' });
  }

  client.publish(topic, message, { qos: 1, retain: retain || false }, (err) => {
    if (err) {
      console.error('Publish error:', err);
      return res.status(500).json({ error: 'Failed to publish message' });
    }
    res.json({ success: true, topic, message });
  });
});

// API: Subscribe to a topic
app.post('/subscribe', (req, res) => {
  const { topic } = req.body;
  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  client.subscribe(topic, { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscribe error:', err);
      return res.status(500).json({ error: 'Failed to subscribe to topic' });
    }
    res.json({ success: true, topic });
  });
});

// API: Get the last message for a topic
app.get('/last-message', (req, res) => {
  const { topic } = req.query;
  console.log('Requested topic:', topic);  // Log the requested topic

  if (!topic) {
    return res.status(400).json({ error: 'Topic is required' });
  }

  // Log current last messages
  console.log('Current lastMessages:', lastMessages);

  const lastMessage = lastMessages[topic];
  if (!lastMessage) {
    return res.status(404).json({ error: 'No message found for the given topic' });
  }

  res.json({
    success: true,
    topic: topic,
    message: lastMessage,
  });
});

// Start the Express server
app.listen(port, () => {
  console.log(`Server running on http://localhost:${port}`);
});
