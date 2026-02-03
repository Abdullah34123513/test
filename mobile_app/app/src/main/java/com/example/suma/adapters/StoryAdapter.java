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
import java.util.List;

public class StoryAdapter extends RecyclerView.Adapter<StoryAdapter.StoryViewHolder> {

    private List<String> storyNames;
    private Context context;

    public StoryAdapter(Context context, List<String> storyNames) {
        this.context = context;
        this.storyNames = storyNames;
    }

    @NonNull
    @Override
    public StoryViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_story, parent, false);
        return new StoryViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull StoryViewHolder holder, int position) {
        String name = storyNames.get(position);
        holder.txtName.setText(name);
        
        // Placeholder images using random picsum
        Glide.with(context)
             .load("https://picsum.photos/200?random=" + position)
             .circleCrop()
             .into(holder.imgAvatar);

        if (position == 0) {
           holder.overlayAdd.setVisibility(View.VISIBLE);
           holder.txtName.setText("Your Story");
        } else {
           holder.overlayAdd.setVisibility(View.GONE);
        }
    }

    @Override
    public int getItemCount() {
        return storyNames.size();
    }

    static class StoryViewHolder extends RecyclerView.ViewHolder {
        ImageView imgAvatar;
        TextView txtName;
        View overlayAdd;

        public StoryViewHolder(@NonNull View itemView) {
            super(itemView);
            imgAvatar = itemView.findViewById(R.id.imgStoryAvatar);
            txtName = itemView.findViewById(R.id.txtStoryName);
            overlayAdd = itemView.findViewById(R.id.overlayAddStory);
        }
    }
}
