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

// Store last messages for topics (in memory)
const lastMessages = {};

// Listen for messages on subscribed topics
client.on('connect', () => {
  console.log('Connected to MQTT broker');
});

app.post('/publish', (req, res) => {
  const { topic, message, retain } = req.body;
  if (!topic || !message) {
    return res.status(400).json({ error: 'Topic and message are required' });
  }

  // Publish with retain flag
  client.publish(topic, message, { qos: 1, retain: retain || false }, (err) => {
    if (err) {
      console.error('Publish error:', err);
      return res.status(500).json({ error: 'Failed to publish message' });
    }
    res.json({ success: true, topic, message });
  });
});

// Handle received messages and store the last message for each topic
client.on('message', (topic, message) => {
  console.log(`Message received on topic ${topic}:`, message.toString());
  lastMessages[topic] = message.toString();  // Store the last message for the topic
});
// API to Subscribe to a specific topic based on device_id and component_id
app.post('/subscribe', (req, res) => {
  const { device_id, component_id } = req.body;

  if (!device_id || !component_id) {
    return res.status(400).json({ error: 'device_id and component_id are required' });
  }

  const topic = `Mazaya/${device_id}/${component_id}`;

  client.subscribe(topic, { qos: 1 }, (err) => {
    if (err) {
      console.error('Subscription error:', err);
      return res.status(500).json({ error: 'Failed to subscribe to topic' });
    }

    // Wait for retained messages or confirm subscription
    setTimeout(() => {
      const lastMessage = lastMessages[topic];
      if (lastMessage) {
        return res.json({ success: true, message: `Subscribed to topic: ${topic}`, last_message: lastMessage });
      } else {
        return res.json({ success: true, message: `Subscribed to topic: ${topic}`, last_message: 'No messages yet for this topic' });
      }
    }, 500); // Delay to allow retained messages to arrive
  });
});


// API to get the last message for a specific topic
app.post('/get-last-message', (req, res) => {
  const { device_id, component_id } = req.body;

  // Validate the request data
  if (!device_id || !component_id) {
    return res.status(400).json({ error: 'device_id and component_id are required' });
  }

  const topic = `Mazaya/${device_id}/${component_id}`;

  // Check if there's a stored message for this topic
  if (lastMessages[topic]) {
    return res.json({ success: true, topic, last_message: lastMessages[topic] });
  } else {
    return res.status(404).json({ error: 'No message found for the given topic' });
  }
});

// Start the Express server
app.listen(port, () => {
  console.log(`Server is running on http://localhost:${port}`);
});
