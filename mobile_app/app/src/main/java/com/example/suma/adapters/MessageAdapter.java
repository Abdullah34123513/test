package com.example.suma.adapters;

import android.content.Context;
import android.media.MediaPlayer;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.SeekBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.suma.AuthManager;
import com.example.suma.R;
import com.example.suma.models.Message;

import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

public class MessageAdapter extends RecyclerView.Adapter<MessageAdapter.MessageViewHolder> {

    private static final int TYPE_SENT = 1;
    private static final int TYPE_RECEIVED = 2;

    private Context context;
    private List<Message> messages;
    private int currentUserId;
    private MediaPlayer mediaPlayer;

    public MessageAdapter(Context context, int currentUserId) {
        this.context = context;
        this.currentUserId = currentUserId;
        this.messages = new ArrayList<>();
    }

    public void setMessages(List<Message> messages) {
        this.messages = messages;
        notifyDataSetChanged();
    }

    public void addMessage(Message message) {
        this.messages.add(message);
        notifyItemInserted(messages.size() - 1);
    }
    
    public List<Message> getMessages() {
        return messages;
    }

    @Override
    public int getItemViewType(int position) {
        if (messages.get(position).getSenderId() == currentUserId) {
            return TYPE_SENT;
        } else {
            return TYPE_RECEIVED;
        }
    }

    @NonNull
    @Override
    public MessageViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view;
        if (viewType == TYPE_SENT) {
            view = LayoutInflater.from(context).inflate(R.layout.item_message_sent, parent, false);
        } else {
            view = LayoutInflater.from(context).inflate(R.layout.item_message_received, parent, false);
        }
        return new MessageViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MessageViewHolder holder, int position) {
        Message message = messages.get(position);
        holder.bind(message);
    }

    @Override
    public int getItemCount() {
        return messages.size();
    }

    class MessageViewHolder extends RecyclerView.ViewHolder {

        TextView textMessage, textTimestamp;
        ImageView imageMessage;
        LinearLayout layoutAudio;
        ImageButton btnPlayAudio;
        SeekBar seekbarAudio;

        public MessageViewHolder(@NonNull View itemView) {
            super(itemView);
            textMessage = itemView.findViewById(R.id.text_message);
            textTimestamp = itemView.findViewById(R.id.text_timestamp);
            imageMessage = itemView.findViewById(R.id.image_message);
            layoutAudio = itemView.findViewById(R.id.layout_audio);
            btnPlayAudio = itemView.findViewById(R.id.btn_play_audio);
            seekbarAudio = itemView.findViewById(R.id.seekbar_audio);
        }

        public void bind(Message message) {
            textTimestamp.setText(message.getCreatedAt());

            if ("text".equals(message.getType())) {
                textMessage.setVisibility(View.VISIBLE);
                textMessage.setText(message.getMessage());
                imageMessage.setVisibility(View.GONE);
                layoutAudio.setVisibility(View.GONE);
            } else if ("image".equals(message.getType())) {
                textMessage.setVisibility(View.GONE); // Or show caption?
                imageMessage.setVisibility(View.VISIBLE);
                layoutAudio.setVisibility(View.GONE);

                String imageUrl = AuthManager.getBaseUrl().replace("/api", "") + "/storage/" + message.getFilePath();
                Glide.with(context)
                        .load(imageUrl)
                        .centerCrop()
                        .into(imageMessage);
                        
                if (message.getMessage() != null && !message.getMessage().isEmpty()) {
                    textMessage.setVisibility(View.VISIBLE);
                    textMessage.setText(message.getMessage());
                }

            } else if ("audio".equals(message.getType())) {
                textMessage.setVisibility(View.GONE);
                imageMessage.setVisibility(View.GONE);
                layoutAudio.setVisibility(View.VISIBLE);

                btnPlayAudio.setOnClickListener(v -> playAudio(message.getFilePath()));
            }
        }
        
        private void playAudio(String path) {
            if (mediaPlayer != null) {
                mediaPlayer.release();
            }
            mediaPlayer = new MediaPlayer();
            try {
                String audioUrl = AuthManager.getBaseUrl().replace("/api", "") + "/storage/" + path;
                mediaPlayer.setDataSource(audioUrl);
                mediaPlayer.prepare();
                mediaPlayer.start();
            } catch (IOException e) {
                e.printStackTrace();
            }
        }
    }
}
