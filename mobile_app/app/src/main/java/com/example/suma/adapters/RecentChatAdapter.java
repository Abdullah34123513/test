package com.example.suma.adapters;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.bumptech.glide.Glide;
import com.example.suma.R;
import com.example.suma.models.UserResponse;
import java.util.ArrayList;
import java.util.List;

public class RecentChatAdapter extends RecyclerView.Adapter<RecentChatAdapter.ChatViewHolder> {

    public static class ChatItem {
        public String name;
        public String lastMessage;
        public String time;
        public int unreadCount;
        public int userId;

        public ChatItem(String name, String lastMessage, String time, int unreadCount, int userId) {
            this.name = name;
            this.lastMessage = lastMessage;
            this.time = time;
            this.unreadCount = unreadCount;
            this.userId = userId;
        }

        // Create ChatItem from UserResponse
        public static ChatItem fromUserResponse(UserResponse user) {
            return new ChatItem(
                user.getName(),
                user.getLastMessage() != null ? user.getLastMessage() : "No messages yet",
                user.getLastMessageTime() != null ? user.getLastMessageTime() : "",
                user.getUnreadCount(),
                user.getId()
            );
        }
    }

    private List<ChatItem> chats;
    private Context context;
    private OnChatClickListener listener;

    public interface OnChatClickListener {
        void onChatClick(int userId);
    }

    public RecentChatAdapter(Context context, List<ChatItem> chats, OnChatClickListener listener) {
        this.context = context;
        this.chats = chats != null ? chats : new ArrayList<>();
        this.listener = listener;
    }

    public void updateChats(List<ChatItem> newChats) {
        this.chats.clear();
        if (newChats != null) {
            this.chats.addAll(newChats);
        }
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ChatViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_chat_recent, parent, false);
        return new ChatViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ChatViewHolder holder, int position) {
        ChatItem chat = chats.get(position);
        holder.txtName.setText(chat.name);
        holder.txtMessage.setText(chat.lastMessage);
        holder.txtTime.setText(chat.time);
        
        if (chat.unreadCount > 0) {
            holder.badge.setVisibility(View.VISIBLE);
            holder.badge.setText(String.valueOf(chat.unreadCount));
            holder.txtTime.setTextColor(context.getResources().getColor(android.R.color.holo_blue_light));
        } else {
            holder.badge.setVisibility(View.GONE);
            holder.txtTime.setTextColor(0xFF637588);
        }

        // Use user ID based avatar (UI Avatars service)
        Glide.with(context)
             .load("https://ui-avatars.com/api/?name=" + chat.name.replace(" ", "+") + "&background=random&size=200")
             .circleCrop()
             .into(holder.imgAvatar);

        holder.itemView.setOnClickListener(v -> listener.onChatClick(chat.userId));
    }

    @Override
    public int getItemCount() {
        return chats.size();
    }

    static class ChatViewHolder extends RecyclerView.ViewHolder {
        ImageView imgAvatar;
        TextView txtName, txtMessage, txtTime, badge;

        public ChatViewHolder(@NonNull View itemView) {
            super(itemView);
            imgAvatar = itemView.findViewById(R.id.imgChatAvatar);
            txtName = itemView.findViewById(R.id.txtChatName);
            txtMessage = itemView.findViewById(R.id.txtLastMessage);
            txtTime = itemView.findViewById(R.id.txtChatTime);
            badge = itemView.findViewById(R.id.badgeUnread);
        }
    }
}

